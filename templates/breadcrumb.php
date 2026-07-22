<?php
/*
|--------------------------------------------------------------------------
| Breadcrumb reutilizable
|--------------------------------------------------------------------------
| Cada página deberá definir previamente un arreglo:
|
| $breadcrumb = [
|     "Inicio" => "/index.php",
|     "Galería de Ferias" => "/ferias.php",
|     "Detalle" => null
| ];
|
| El último elemento (null) representa la página actual.
|--------------------------------------------------------------------------
*/

if (!isset($breadcrumb) || !is_array($breadcrumb) || empty($breadcrumb)) {
    return;
}
?>

<nav
    aria-label="breadcrumb"
    class="mb-4"
>

    <ol class="breadcrumb bg-light rounded shadow-sm px-3 py-2">

        <?php
        $ultimo = array_key_last($breadcrumb);

        foreach ($breadcrumb as $texto => $url) :
        ?>

            <?php if ($texto === $ultimo || empty($url)) : ?>

                <li
                    class="breadcrumb-item active fw-semibold"
                    aria-current="page"
                >
                    <?= htmlspecialchars($texto) ?>
                </li>

            <?php else : ?>

                <li class="breadcrumb-item">

                    <a
                        href="<?= htmlspecialchars($url) ?>"
                        class="text-decoration-none"
                    >
                        <?= htmlspecialchars($texto) ?>
                    </a>

                </li>

            <?php endif; ?>

        <?php endforeach; ?>

    </ol>

</nav>