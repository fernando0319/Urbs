<?php if (!empty($errors)): ?>
<div class="flash flash-warn" role="alert">
  <strong>Revise o formulário:</strong>
  <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>
