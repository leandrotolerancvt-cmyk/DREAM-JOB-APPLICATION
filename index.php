<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Application.php';

$app = new Application(Database::connect());
$errors = [];
$flash  = $_GET['msg'] ?? '';

$educationLevels = ['High School','Associate Degree',"Bachelor's Degree","Master's Degree",'Doctorate'];
$tools = ['SQL','Python','R','Excel','Power BI','Tableau'];

function clean(array $src): array {
    $f = ['first_name','last_name','email','phone','education_level','years_experience',
          'primary_tool','portfolio_url','expected_salary'];
    $d = [];
    foreach ($f as $k) { $d[$k] = trim($src[$k] ?? ''); }
    return $d;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id) {
        $app->delete($id);
        header('Location: index.php?msg=deleted'); exit;
    }

    $data = clean($_POST);
    if ($data['first_name'] === '') $errors[] = 'First name is required.';
    if ($data['last_name'] === '')  $errors[] = 'Last name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($data['phone'] === '') $errors[] = 'Phone number is required.';
    if (!in_array($data['education_level'], $educationLevels, true)) $errors[] = 'Select an education level.';
    if (!ctype_digit($data['years_experience']) || (int)$data['years_experience'] > 60) $errors[] = 'Years of experience must be 0-60.';
    if (!in_array($data['primary_tool'], $tools, true)) $errors[] = 'Select a primary tool.';
    if ($data['portfolio_url'] !== '' && !filter_var($data['portfolio_url'], FILTER_VALIDATE_URL)) $errors[] = 'Portfolio URL is not valid.';
    if (!is_numeric($data['expected_salary']) || $data['expected_salary'] < 0) $errors[] = 'Expected salary must be a positive number.';

    if (!$errors) {
        if ($action === 'update' && $id) { $app->update($id, $data); header('Location: index.php?msg=updated'); exit; }
        if ($action === 'create')        { $app->create($data);      header('Location: index.php?msg=created'); exit; }
    }
    $editing = $data + ['id' => $id];   // redisplay entered values
} elseif (isset($_GET['edit'])) {
    $editing = $app->find((int)$_GET['edit']);
}
$editing = $editing ?? null;
$isEdit  = $editing && !empty($editing['id']);
$v = fn($k) => htmlspecialchars((string)($editing[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$rows = $app->all();
$messages = ['created'=>'Application submitted.','updated'=>'Application updated.','deleted'=>'Application deleted.'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Analyst Applications</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
  <h1>Data Analyst Job Application</h1>
  <p>Apply for your dream job, then manage submitted applications below.</p>
</header>
<main>
<?php if (isset($messages[$flash])): ?><div class="alert ok"><?= $e($messages[$flash]) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $m): ?><li><?= $e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<section class="card">
  <h2><?= $isEdit ? 'Update Application #' . (int)$editing['id'] : 'New Application' ?></h2>
  <form method="post" action="index.php">
    <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= $isEdit ? (int)$editing['id'] : '' ?>">
    <div class="grid">
      <label>First name<input name="first_name" value="<?= $v('first_name') ?>" maxlength="50" required></label>
      <label>Last name<input name="last_name" value="<?= $v('last_name') ?>" maxlength="50" required></label>
      <label>Email<input type="email" name="email" value="<?= $v('email') ?>" maxlength="100" required></label>
      <label>Phone<input name="phone" value="<?= $v('phone') ?>" maxlength="20" required></label>
      <label>Education level
        <select name="education_level" required>
          <option value="">-- Select --</option>
          <?php foreach ($educationLevels as $o): ?><option <?= $v('education_level') === $o ? 'selected' : '' ?>><?= $e($o) ?></option><?php endforeach; ?>
        </select></label>
      <label>Years of experience<input type="number" name="years_experience" min="0" max="60" value="<?= $v('years_experience') ?: '0' ?>" required></label>
      <label>Primary tool
        <select name="primary_tool" required>
          <option value="">-- Select --</option>
          <?php foreach ($tools as $o): ?><option <?= $v('primary_tool') === $o ? 'selected' : '' ?>><?= $e($o) ?></option><?php endforeach; ?>
        </select></label>
      <label>Expected salary (monthly, PHP)<input type="number" step="0.01" min="0" name="expected_salary" value="<?= $v('expected_salary') ?>" required></label>
      <label class="wide">Portfolio URL (optional)<input type="url" name="portfolio_url" value="<?= $v('portfolio_url') ?>" maxlength="255" placeholder="https://"></label>
    </div>
    <div class="actions">
      <button class="btn primary" type="submit"><?= $isEdit ? 'Update Application' : 'Submit Application' ?></button>
      <?php if ($isEdit): ?><a class="btn" href="index.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</section>

<section class="card">
  <h2>Submitted Applications (<?= count($rows) ?>)</h2>
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Education</th><th>Exp. (yrs)</th>
      <th>Primary tool</th><th>Portfolio</th><th>Expected salary</th><th>Date added</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="11" class="empty">No applications yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= $e($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= $e($r['email']) ?></td>
        <td><?= $e($r['phone']) ?></td>
        <td><?= $e($r['education_level']) ?></td>
        <td><?= (int)$r['years_experience'] ?></td>
        <td><?= $e($r['primary_tool']) ?></td>
        <td><?php if ($r['portfolio_url']): ?><a href="<?= $e($r['portfolio_url']) ?>" target="_blank" rel="noopener">Link</a><?php else: ?>&ndash;<?php endif; ?></td>
        <td><?= number_format((float)$r['expected_salary'], 2) ?></td>
        <td><?= $e($r['date_added']) ?></td>
        <td class="row-actions">
          <a class="btn small" href="index.php?edit=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" action="index.php" onsubmit="return confirm('Delete this application?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</section>
</main>
</body>
</html>
