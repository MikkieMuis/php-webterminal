<?php
// ============================================================
//  editor commands: nano, __nano_save
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)
// ============================================================

switch ($cmd) {

    // ── nano ──
    case 'nano':
        if ($args === '') err('nano: missing filename');
        $path    = res_path($args);
        $isNew   = !isset($_SESSION['fs'][$path]);
        $content = '';
        if (!$isNew) {
            if ($_SESSION['fs'][$path]['type'] === 'dir') {
                err('nano: ' . $args . ': Is a directory');
            }
            $content = $_SESSION['fs'][$path]['content'] ?? '';
        }
        echo json_encode([
            'output'   => '',
            'nano'     => true,
            'path'     => $path,
            'filename' => basename($path),
            'content'  => $content,
            'isnew'    => $isNew,
        ]);
        exit;

    // ── __nano_save (internal — called by JS nano overlay) ──
    case '__nano_save':
        $savePath    = isset($body['path'])    ? $body['path']    : '';
        $saveContent = isset($body['content']) ? $body['content'] : '';
        if ($savePath === '') err('nano_save: missing path');
        $savePath = res_path($savePath);
        $parent   = dirname($savePath);
        if (!isset($_SESSION['fs'][$parent])) {
            err('nano_save: ' . $savePath . ': No such directory');
        }
        $_SESSION['fs'][$savePath] = [
            'type'    => 'file',
            'content' => $saveContent,
            'mtime'   => time(),
        ];
        $lines = substr_count($saveContent, "\n") + (strlen($saveContent) > 0 ? 1 : 0);
        echo json_encode(['output' => '', 'saved' => true, 'lines' => $lines]);
        exit;
}
