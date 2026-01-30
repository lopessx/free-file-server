<?php if (count($errors) > 0): //Basicamente vai fazer a contagem dos erros do servidor e mostrar numa view na página cada erro específico?>
<div class="error">
    <?php foreach ($errors as $error): ?>
    <p><?php echo $error; ?> </p>
    <?php endforeach ?>
</div>
<?php endif ?>