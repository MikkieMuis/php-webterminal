<?php
//  editor commands: nano, joe, __nano_save
//  Receives: $cmd, $args, $argv, $user, $body  (from terminal.php scope)

switch ($cmd) {

    // nano
    case 'nano':
        if ($args === '') {
            // no filename — open a new empty buffer (like real nano)
            $path    = res_path('New Buffer');
            $isNew   = true;
            $content = '';
        } else {
            $path    = res_path($args);
            $isNew   = !isset($_SESSION['fs'][$path]);
            $content = '';
            if (!$isNew) {
                if ($_SESSION['fs'][$path]['type'] === 'dir') {
                    err('nano: ' . $args . ': Is a directory');
                }
                $content = $_SESSION['fs'][$path]['content'] ?? '';
            }
        }
        echo json_encode([
            'output'   => '',
            'nano'     => true,
            'path'     => $path,
            'filename' => $args === '' ? 'New Buffer' : basename($path),
            'content'  => $content,
            'isnew'    => $isNew,
        ]);
        exit;

    // joe
    case 'joe':
        if ($args === '') {
            // no filename — open a new empty buffer
            $path    = res_path('New Buffer');
            $isNew   = true;
            $content = '';
        } else {
            $path    = res_path($args);
            $isNew   = !isset($_SESSION['fs'][$path]);
            $content = '';
            if (!$isNew) {
                if ($_SESSION['fs'][$path]['type'] === 'dir') {
                    err('joe: ' . $args . ': Is a directory');
                }
                $content = $_SESSION['fs'][$path]['content'] ?? '';
            }
        }
        echo json_encode([
            'output'   => '',
            'joe'      => true,
            'path'     => $path,
            'filename' => $args === '' ? 'New Buffer' : basename($path),
            'content'  => $content,
            'isnew'    => $isNew,
        ]);
        exit;

    // __nano_save (internal — called by JS nano/joe overlay)
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
        $lines = substr_count($saveContent, "\n");
        if (strlen($saveContent) > 0 && substr($saveContent, -1) !== "\n") $lines++;
        echo json_encode(['output' => '', 'saved' => true, 'lines' => $lines]);
        exit;
}
