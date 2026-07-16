<?php
define('APP', true);
require_once __DIR__ . '/../inc/bootstrap.php';
require_once INC_DIR . '/auth.php';
require_once INC_DIR . '/bookings.php';
require_once __DIR__ . '/_layout.php';

if (!IS_CONFIGURED) { header('Location: setup.php'); exit; }
require_login();

$services = booking_services();

/* ── .ics letöltés egy foglaláshoz ────────────────────────────── */
if (isset($_GET['ics'])) {
    $b = booking_get($_GET['ics']);
    if ($b) {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="foglalas-' . $b['date'] . '.ics"');
        echo booking_ics($b);
        exit;
    }
    http_response_code(404); exit('Nem található.');
}

/* ── Műveletek (visszaigazolás / lemondás / törlés) ───────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = isset($_POST['id']) ? $_POST['id'] : '';
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'confirm' && booking_set_status($id, 'confirmed')) {
        flash_set('success', 'Foglalás visszaigazolva.');
    } elseif ($action === 'cancel' && booking_set_status($id, 'cancelled')) {
        flash_set('success', 'Foglalás lemondva – az idősáv újra szabad.');
    } elseif ($action === 'delete' && booking_delete($id)) {
        flash_set('success', 'Foglalás törölve.');
    } else {
        flash_set('error', 'A művelet nem sikerült.');
    }
    $q = isset($_POST['ym']) && preg_match('/^\d{4}-\d{2}$/', $_POST['ym']) ? '?ym=' . $_POST['ym'] : '';
    header('Location: bookings.php' . $q . '#lista'); exit;
}

/* ── Megjelenítendő hónap ─────────────────────────────────────── */
$ym = isset($_GET['ym']) && preg_match('/^\d{4}-\d{2}$/', $_GET['ym']) ? $_GET['ym'] : date('Y-m');
$monthStart = strtotime($ym . '-01');
$prevYm = date('Y-m', strtotime('-1 month', $monthStart));
$nextYm = date('Y-m', strtotime('+1 month', $monthStart));
$daysInMonth = (int)date('t', $monthStart);
$leadDow = (int)date('N', $monthStart); // 1=hétfő

$huMonths = array(1=>'január','február','március','április','május','június','július','augusztus','szeptember','október','november','december');
$monthLabel = date('Y', $monthStart) . '. ' . $huMonths[(int)date('n', $monthStart)];

/* Foglalások csoportosítása a hónapon belül nap szerint. */
$all = bookings_all();
$byDay = array();
$monthList = array();
foreach ($all as $b) {
    if (substr($b['date'], 0, 7) === $ym) {
        $byDay[$b['date']][] = $b;
        $monthList[] = $b;
    }
}

/* Statisztika: közelgő aktív foglalások (mától). */
$today = date('Y-m-d');
$upcoming = 0;
foreach ($all as $b) {
    if ($b['status'] !== 'cancelled' && $b['date'] >= $today) $upcoming++;
}

function bk_status_label($s) {
    if ($s === 'confirmed') return array('Visszaigazolva', 'ok');
    if ($s === 'cancelled') return array('Lemondva', 'cancel');
    return array('Új', 'new');
}

admin_head('Foglalások', 'bookings');
?>
<?php flash_render(); ?>
<div class="adm-pagehead">
  <div>
    <h1>Foglalások</h1>
    <p><?= (int)$upcoming ?> közelgő foglalás · online időpontkérések</p>
  </div>
  <div class="bk-monthnav">
    <a class="btn btn-ghost btn-sm" href="?ym=<?= e($prevYm) ?>">‹ Előző</a>
    <span class="bk-monthlabel"><?= e($monthLabel) ?></span>
    <a class="btn btn-ghost btn-sm" href="?ym=<?= e($nextYm) ?>">Következő ›</a>
  </div>
</div>

<div class="adm-card bk-calwrap">
  <div class="bk-cal">
    <div class="bk-cal-head">
      <div>Hé</div><div>Ke</div><div>Sze</div><div>Csü</div><div>Pé</div><div>Szo</div><div>Va</div>
    </div>
    <div class="bk-cal-grid">
      <?php for ($i = 1; $i < $leadDow; $i++): ?>
        <div class="bk-day bk-day-empty"></div>
      <?php endfor; ?>
      <?php for ($d = 1; $d <= $daysInMonth; $d++):
        $date = sprintf('%s-%02d', $ym, $d);
        $dayBookings = isset($byDay[$date]) ? $byDay[$date] : array();
        usort($dayBookings, function ($a, $b) { return strcmp($a['time'], $b['time']); });
        $isToday = ($date === $today);
      ?>
        <div class="bk-day<?= $isToday ? ' bk-day-today' : '' ?>">
          <div class="bk-day-num"><?= $d ?></div>
          <?php foreach ($dayBookings as $b): [$lbl, $cls] = bk_status_label($b['status']); ?>
            <a class="bk-chip bk-chip-<?= $cls ?>" href="#b-<?= e($b['id']) ?>" title="<?= e($b['name']) ?>">
              <strong><?= e($b['time']) ?></strong> <?= e($b['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
  <div class="bk-legend">
    <span><i class="bk-dot bk-dot-new"></i> Új</span>
    <span><i class="bk-dot bk-dot-ok"></i> Visszaigazolva</span>
    <span><i class="bk-dot bk-dot-cancel"></i> Lemondva</span>
  </div>
</div>

<h2 class="bk-listhead" id="lista"><?= e($monthLabel) ?> – foglalások</h2>
<?php if (!$monthList): ?>
  <div class="adm-card adm-empty">
    <h2>Ebben a hónapban nincs foglalás</h2>
    <p>Az ügyfelek a szolgáltatásoldalakon tudnak online időpontot foglalni.</p>
  </div>
<?php else:
  usort($monthList, function ($a, $b) { return strcmp($a['date'] . $a['time'], $b['date'] . $b['time']); });
?>
  <div class="bk-cards">
    <?php foreach ($monthList as $b):
      [$lbl, $cls] = bk_status_label($b['status']);
      $svc = isset($services[$b['service']]) ? $services[$b['service']] : $b['service'];
    ?>
      <div class="adm-card bk-card bk-card-<?= $cls ?>" id="b-<?= e($b['id']) ?>">
        <div class="bk-card-when">
          <div class="bk-card-date"><?= e(hu_date_short($b['date'])) ?></div>
          <div class="bk-card-time"><?= e($b['time']) ?></div>
          <span class="badge bk-badge-<?= $cls ?>"><?= e($lbl) ?></span>
        </div>
        <div class="bk-card-body">
          <div class="bk-card-svc"><?= e($svc) ?></div>
          <div class="bk-card-name"><?= e($b['name']) ?></div>
          <div class="bk-card-meta">
            <a href="tel:<?= e(preg_replace('/\s+/', '', $b['phone'])) ?>"><?= e($b['phone']) ?></a>
            <?php if ($b['email'] !== ''): ?><span>·</span><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a><?php endif; ?>
          </div>
          <?php if ($b['address'] !== ''): ?><div class="bk-card-addr">📍 <?= e($b['address']) ?></div><?php endif; ?>
          <?php if ($b['note'] !== ''): ?><div class="bk-card-note">„<?= e($b['note']) ?>”</div><?php endif; ?>
        </div>
        <div class="bk-card-actions">
          <a class="btn btn-ghost btn-sm" href="?ics=<?= e($b['id']) ?>">Naptárba (.ics)</a>
          <?php if ($b['status'] !== 'confirmed'): ?>
          <form method="post" style="display:inline">
            <?= csrf_field() ?><input type="hidden" name="ym" value="<?= e($ym) ?>"><input type="hidden" name="id" value="<?= e($b['id']) ?>"><input type="hidden" name="action" value="confirm">
            <button class="btn btn-primary btn-sm" type="submit">Visszaigazolás</button>
          </form>
          <?php endif; ?>
          <?php if ($b['status'] !== 'cancelled'): ?>
          <form method="post" style="display:inline" onsubmit="return confirm('Biztosan lemondja ezt a foglalást? Az idősáv újra szabaddá válik.');">
            <?= csrf_field() ?><input type="hidden" name="ym" value="<?= e($ym) ?>"><input type="hidden" name="id" value="<?= e($b['id']) ?>"><input type="hidden" name="action" value="cancel">
            <button class="btn btn-ghost btn-sm" type="submit">Lemondás</button>
          </form>
          <?php endif; ?>
          <form method="post" style="display:inline" onsubmit="return confirm('Végleg törli ezt a foglalást?');">
            <?= csrf_field() ?><input type="hidden" name="ym" value="<?= e($ym) ?>"><input type="hidden" name="id" value="<?= e($b['id']) ?>"><input type="hidden" name="action" value="delete">
            <button class="btn btn-danger btn-sm" type="submit">Törlés</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_foot(); ?>
