<?php
require_once 'src/Book.php';
require_once 'src/Member.php';
require_once 'src/Loan.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['action'] ?? '') {
            case 'borrow':
                Loan::borrow((int)$_POST['book_id'], (int)$_POST['member_id']);
                $message = 'ยืมหนังสือสำเร็จ';
                break;
            case 'return':
                Loan::returnBook((int)$_POST['loan_id']);
                $message = 'คืนหนังสือสำเร็จ';
                break;
            case 'add_book':
                Book::add(trim($_POST['title']), trim($_POST['author']), trim($_POST['isbn']), max(1, (int)$_POST['copies']));
                $message = 'เพิ่มหนังสือสำเร็จ';
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$books   = Book::all();
$members = Member::all();
$loans   = Loan::active();
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ระบบยืม-คืนหนังสือ | MB Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] } } } }
</script>
</head>
<body class="bg-green-50/60 font-sans text-slate-700 antialiased">

<!-- Header -->
<header class="bg-gradient-to-r from-green-700 to-green-500 text-white shadow-lg">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-6">
    <div class="flex items-center gap-3">
      <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 text-2xl">
        <i class="bi bi-gear"></i>
      </div>
      <div>
        <h1 class="text-xl font-semibold leading-tight">MB Library</h1>
        <p class="text-sm text-green-100">Management</p>
      </div>
    </div>
    <div class="hidden gap-3 sm:flex">
      <div class="rounded-xl bg-white/15 px-4 py-2 text-center">
        <div class="text-xl font-semibold"><?= count($books) ?></div>
        <div class="text-xs text-green-100">ชื่อเรื่อง</div>
      </div>
      <div class="rounded-xl bg-white/15 px-4 py-2 text-center">
        <div class="text-xl font-semibold"><?= count($loans) ?></div>
        <div class="text-xs text-green-100">กำลังยืม</div>
      </div>
    </div>
  </div>
</header>

<main class="mx-auto max-w-6xl space-y-8 px-4 py-8">

  <!-- Flash messages -->
  <?php if ($message): ?>
    <div class="flex items-center gap-2 rounded-xl border border-green-200 bg-green-100 px-4 py-3 text-green-800">
      <i class="bi bi-check-square-fill"></i> <span><?= h($message) ?></span>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
      <i class="bi bi-exclamation-triangle-fill"></i> <span><?= h($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Books -->
  <section>
    <h2 class="mb-4 text-lg font-semibold text-slate-800">รายการหนังสือ</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($books as $b): ?>
      <div class="flex flex-col justify-between rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-100 transition hover:shadow-md hover:ring-green-300">
        <div>
          <div class="mb-3 flex items-start justify-between gap-2">
            <h3 class="font-semibold text-slate-800"><?= h($b->getTitle()) ?></h3>
            <?php if ($b->isAvailable()): ?>
              <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">ว่าง <?= $b->getAvailableCopies() ?></span>
            <?php else: ?>
              <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">ไม่ว่าง</span>
            <?php endif; ?>
          </div>
          <p class="text-sm text-slate-500">โดย <?= h($b->getAuthor()) ?></p>
        </div>

        <?php if ($b->isAvailable()): ?>
        <form method="post" class="mt-5 flex gap-2">
          <input type="hidden" name="action" value="borrow">
          <input type="hidden" name="book_id" value="<?= $b->getId() ?>">
          <select name="member_id" required
                  class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
            <?php foreach ($members as $m): ?>
              <option value="<?= $m->getId() ?>"><?= h($m->getName()) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-700 active:scale-95">ยืม</button>
        </form>
        <?php else: ?>
        <button disabled class="mt-5 cursor-not-allowed rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-400">ไม่สามารถยืมได้</button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Active loans -->
  <section>
    <h2 class="mb-4 text-lg font-semibold text-slate-800">รายการที่กำลังยืม</h2>
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-green-100">
      <table class="w-full text-left text-sm">
        <thead class="bg-green-600 text-white">
          <tr>
            <th class="px-5 py-3 font-medium">หนังสือ</th>
            <th class="px-5 py-3 font-medium">ผู้ยืม</th>
            <th class="px-5 py-3 font-medium">วันที่ยืม</th>
            <th class="px-5 py-3 font-medium">กำหนดคืน</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-green-50">
          <?php foreach ($loans as $l): ?>
          <tr class="hover:bg-green-50/50">
            <td class="px-5 py-3 font-medium text-slate-800"><?= h($l['title']) ?></td>
            <td class="px-5 py-3"><?= h($l['name']) ?></td>
            <td class="whitespace-nowrap px-5 py-3 text-slate-500"><?= h($l['borrowed_at']) ?></td>
            <td class="whitespace-nowrap px-5 py-3">
              <?php if ($l['overdue']): ?>
                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700"><?= h($l['due_date']) ?> · เกินกำหนด</span>
              <?php else: ?>
                <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700"><?= h($l['due_date']) ?></span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3 text-right">
              <form method="post">
                <input type="hidden" name="action" value="return">
                <input type="hidden" name="loan_id" value="<?= $l['id'] ?>">
                <button class="rounded-lg border border-green-600 px-3 py-1.5 text-xs font-medium text-green-700 transition hover:bg-green-600 hover:text-white">คืนหนังสือ</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$loans): ?>
          <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">ยังไม่มีรายการยืม</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Add book -->
  <section>
    <h2 class="mb-4 text-lg font-semibold text-slate-800">เพิ่มหนังสือใหม่</h2>
    <form method="post" class="grid gap-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-100 sm:grid-cols-2 lg:grid-cols-[2fr_1.5fr_1.5fr_90px_auto]">
      <input type="hidden" name="action" value="add_book">
      <input name="title" placeholder="ชื่อหนังสือ" required
             class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
      <input name="author" placeholder="ผู้แต่ง" required
             class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
      <input name="isbn" placeholder="ISBN"
             class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
      <input name="copies" type="number" min="1" value="1"
             class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
      <button class="rounded-lg bg-green-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-green-700 active:scale-95">+ เพิ่ม</button>
    </form>
  </section>
</main>

<footer class="py-8 text-center text-xs text-slate-400">MB Library</footer>
</body>
</html>
