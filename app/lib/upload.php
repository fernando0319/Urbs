<?php
declare(strict_types=1);

/**
 * Uploads: imagens são sempre recodificadas com GD (remove EXIF, inclusive GPS,
 * reduz tamanho e gera miniatura). PDFs são validados pelo conteúdo.
 */

const IMG_MAX_SIDE = 1920;
const THUMB_MAX_SIDE = 480;
const SHARE_MAX_SIDE = 1200;

/** Normaliza $_FILES['campo'] (simples ou múltiplo) numa lista de arquivos válidos. */
function uploaded_files(string $field): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f) {
        return [];
    }
    if (!is_array($f['name'])) {
        return ($f['error'] === UPLOAD_ERR_NO_FILE) ? [] : [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = [
            'name' => $name,
            'type' => $f['type'][$i],
            'tmp_name' => $f['tmp_name'][$i],
            'error' => $f['error'][$i],
            'size' => $f['size'][$i],
        ];
    }
    return $out;
}

function upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo grande demais.',
        UPLOAD_ERR_PARTIAL => 'O envio foi interrompido. Tente de novo.',
        default => 'Não foi possível receber o arquivo.',
    };
}

function upload_dir(string $base = 'uploads'): array
{
    $rel = gmdate('Y/m');
    $abs = ROOT . '/' . $base . '/' . $rel;
    if (!is_dir($abs) && !mkdir($abs, 0755, true) && !is_dir($abs)) {
        throw new RuntimeException('Não foi possível criar a pasta de uploads.');
    }
    return [$rel, $abs];
}

/**
 * Salva uma imagem. Retorna ['file' => ..., 'thumb' => ...] com caminhos relativos a /uploads.
 * Lança RuntimeException com mensagem amigável em caso de erro.
 */
function save_image(array $f): array
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($f['error']));
    }
    $maxMb = (int)config('uploads.max_image_mb', 10);
    if ($f['size'] > $maxMb * 1024 * 1024) {
        throw new RuntimeException("Imagem acima de {$maxMb} MB.");
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
        throw new RuntimeException('Formato de imagem não suportado. Use JPG, PNG ou WebP.');
    }
    if ($info[0] * $info[1] > 50_000_000) {
        throw new RuntimeException('Imagem com resolução grande demais.');
    }
    $src = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
    if (!$src) {
        throw new RuntimeException('Não foi possível ler a imagem.');
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($f['tmp_name']);
        $src = match ((int)($exif['Orientation'] ?? 1)) {
            3 => imagerotate($src, 180, 0),
            6 => imagerotate($src, -90, 0),
            8 => imagerotate($src, 90, 0),
            default => $src,
        };
    }
    [$rel, $abs] = upload_dir();
    $name = bin2hex(random_bytes(10));
    write_resized($src, $abs . "/$name.jpg", IMG_MAX_SIDE, 84);
    write_resized($src, $abs . "/{$name}_t.jpg", THUMB_MAX_SIDE, 78);
    write_resized($src, $abs . "/{$name}_o.jpg", SHARE_MAX_SIDE, 78); // prévia leve para redes sociais
    imagedestroy($src);
    return ['file' => "$rel/$name.jpg", 'thumb' => "$rel/{$name}_t.jpg"];
}

function write_resized(GdImage $src, string $dest, int $maxSide, int $quality): void
{
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // fundo branco p/ PNG transparente
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imageinterlace($dst, true);
    imagejpeg($dst, $dest, $quality);
    imagedestroy($dst);
}

/** Salva PDF (plantas, pranchas). Retorna caminho relativo a /uploads. */
function save_pdf(array $f): string
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($f['error']));
    }
    $maxMb = (int)config('uploads.max_pdf_mb', 20);
    if ($f['size'] > $maxMb * 1024 * 1024) {
        throw new RuntimeException("PDF acima de {$maxMb} MB.");
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $head = (string)file_get_contents($f['tmp_name'], false, null, 0, 5);
    if ($mime !== 'application/pdf' || $head !== '%PDF-') {
        throw new RuntimeException('O arquivo enviado não é um PDF válido.');
    }
    [$rel, $abs] = upload_dir();
    $name = bin2hex(random_bytes(10)) . '.pdf';
    if (!move_uploaded_file($f['tmp_name'], "$abs/$name")) {
        throw new RuntimeException('Falha ao salvar o PDF.');
    }
    return "$rel/$name";
}

/** Arquivo privado (documentos de verificação): fica fora de /uploads e só moderadores acessam. */
function save_private_file(array $f): string
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($f['error']));
    }
    if ($f['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('Documento acima de 10 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) {
        throw new RuntimeException('Envie o documento em PDF, JPG ou PNG.');
    }
    [$rel, $abs] = upload_dir('storage/private');
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], "$abs/$name")) {
        throw new RuntimeException('Falha ao salvar o documento.');
    }
    return "$rel/$name";
}

function delete_upload(?string $rel, string $base = 'uploads'): void
{
    if (!$rel || str_contains($rel, '..')) {
        return;
    }
    $path = ROOT . '/' . $base . '/' . $rel;
    if (is_file($path)) {
        @unlink($path);
    }
    // Remove também a versão de prévia (_o) gerada junto com a imagem.
    $medium = (string)preg_replace('/\.jpg$/', '_o.jpg', $path);
    if ($medium !== $path && is_file($medium)) {
        @unlink($medium);
    }
}
