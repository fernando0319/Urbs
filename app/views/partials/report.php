<?php /** @var string $type @var int $id */ ?>
<?php if (current_user()): ?>
<details class="report">
  <summary><?= icon('flag') ?>Denunciar</summary>
  <form method="post" action="<?= e(url('/denunciar')) ?>" class="report-form">
    <?= csrf_field() ?>
    <input type="hidden" name="target_type" value="<?= e($type) ?>">
    <input type="hidden" name="target_id" value="<?= (int)$id ?>">
    <label>Motivo
      <select name="reason" required>
        <option value="">Escolha…</option>
        <?php foreach (report_reasons() as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach ?>
      </select>
    </label>
    <label>Detalhes (opcional)<textarea name="details" rows="2" maxlength="1000"></textarea></label>
    <button class="btn btn-sm">Enviar denúncia</button>
  </form>
</details>
<?php endif ?>
