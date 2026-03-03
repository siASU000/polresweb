<?php

$dir = __DIR__;

function removeComments($content)
{
    $tokens = token_get_all($content);
    $result = '';

    foreach ($tokens as $token) {
        if (is_string($token)) {
            $result .= $token;
        } else {
            list($id, $text) = $token;

            // Hapus komentar PHP (single line & multi line)
            if ($id == T_COMMENT || $id == T_DOC_COMMENT) {
                // Simpan newline agar kode setelah single-line comment tidak error
                if (strpos(ltrim($text), '//') === 0 || strpos(ltrim($text), '#') === 0) {
                    $result .= "\n";
                }
                continue;
            }

            // Hapus komentar HTML
            if ($id == T_INLINE_HTML) {
                $text = preg_replace('/<!--(?:(?!-->).)*-->/s', '', $text);
            }

            $result .= $text;
        }
    }

    // Rapikan baris kosong yang berlebihan agar lebih bersih
    $result = preg_replace("/\n\s*\n\s*\n/", "\n\n", $result);
    return $result;
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;

echo "Memulai penghapusan komentar...\n";

foreach ($iterator as $file) {
    // Hanya proses file .php dan abaikan file ini sendiri agar tidak error
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== basename(__FILE__)) {
        $path = $file->getRealPath();

        // Lewati folder vendor (composer) jika ada
        if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
            continue;
        }

        $originalContent = file_get_contents($path);

        try {
            $newContent = removeComments($originalContent);

            if ($originalContent !== $newContent) {
                file_put_contents($path, $newContent);
                echo "Dibersihkan: " . str_replace($dir, '', $path) . "\n";
                $count++;
            }
        } catch (Exception $e) {
            echo "Error pada file {$path}\n";
        }
    }
}

echo "Selesai! Berhasil membersihkan komentar pada $count file.\n";
