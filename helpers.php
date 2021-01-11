<?php

function view(string $view, array $args) {
    $content  = viewBuffer('resources/views/' . $view . '.php', $args);
    return viewBuffer('resources/views/layouts/main.php', ['content' => $content]);
}

function viewBuffer($viewPath, $args) {
    extract($args);
    ob_start();
    include $viewPath;
    $response = ob_get_contents();
    ob_end_clean();

    return $response;
}