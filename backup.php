<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
umask(0002); // new files 664, dirs 775

// 1) method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  $p=json_encode(['ok'=>false,'error'=>'method_not_allowed']);
  header('Content-Length: '.strlen($p)); echo $p; exit;
}

// 2) file presence - รองรับหลายไฟล์
$uploadedFiles = [];
$errors = [];

// ตรวจสอบไฟล์ที่ส่งมา
foreach ($_FILES as $fieldName => $fileData) {
  if (is_array($fileData['error'])) {
    // กรณีส่งหลายไฟล์ใน field เดียว
    for ($i = 0; $i < count($fileData['error']); $i++) {
      if ($fileData['error'][$i] === UPLOAD_ERR_OK) {
        $uploadedFiles[] = [
          'field' => $fieldName,
          'index' => $i,
          'name' => $fileData['name'][$i],
          'tmp_name' => $fileData['tmp_name'][$i],
          'size' => $fileData['size'][$i]
        ];
      } else {
        $errors[] = "File {$fieldName}[{$i}]: " . ($fileData['error'][$i] ?? 'unknown_error');
      }
    }
  } else {
    // กรณีส่งไฟล์เดียว
    if ($fileData['error'] === UPLOAD_ERR_OK) {
      $uploadedFiles[] = [
        'field' => $fieldName,
        'index' => null,
        'name' => $fileData['name'],
        'tmp_name' => $fileData['tmp_name'],
        'size' => $fileData['size']
      ];
    } else {
      $errors[] = "File {$fieldName}: " . ($fileData['error'] ?? 'unknown_error');
    }
  }
}

if (empty($uploadedFiles)) {
  http_response_code(400);
  $p=json_encode(['ok'=>false,'error'=>'no_valid_files','details'=>$errors]);
  header('Content-Length: '.strlen($p)); echo $p; exit;
}

// 3) allowlist ขนาด/นามสกุล (กันเผลอทับ index.html/php)
$MAX = 50 * 1024 * 1024; // 50MB ปรับได้
$processedFiles = [];

foreach ($uploadedFiles as $file) {
  // ตรวจสอบขนาดไฟล์
  if ($file['size'] > $MAX) {
    http_response_code(413);
    $p=json_encode(['ok'=>false,'error'=>'file_too_large','file'=>$file['name'],'limit_bytes'=>$MAX]);
    header('Content-Length: '.strlen($p)); echo $p; exit;
  }
  
  // ตรวจสอบนามสกุลไฟล์
  $orig = basename($file['name']);
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  if (!in_array($ext, ['csv','txt'], true)) {
    http_response_code(415);
    $p=json_encode(['ok'=>false,'error'=>'unsupported_ext','file'=>$file['name'],'ext'=>$ext]);
    header('Content-Length: '.strlen($p)); echo $p; exit;
  }
  
  $processedFiles[] = [
    'original_name' => $orig,
    'extension' => $ext,
    'size' => $file['size'],
    'tmp_name' => $file['tmp_name'],
    'field' => $file['field']
  ];
}

// 4) ปลายทาง: ใช้ directory ปัจจุบันแทน
$baseDir = __DIR__ . '/';           // ใช้โฟลเดอร์ uploads ใน directory เดียวกัน

// ตรวจสอบและสร้าง directory
if (!is_dir($baseDir)) {
  if (!mkdir($baseDir, 0775, true)) {
    http_response_code(500);
    $p=json_encode(['ok'=>false,'error'=>'cannot_create_directory','path'=>$baseDir]);
    header('Content-Length: '.strlen($p)); echo $p; exit;
  }
}

// ตรวจสอบสิทธิ์เขียน
if (!is_writable($baseDir)) {
  http_response_code(500);
  $p=json_encode(['ok'=>false,'error'=>'directory_not_writable','path'=>$baseDir]);
  header('Content-Length: '.strlen($p)); echo $p; exit;
}

// lock กันชนกัน
$lockFp = fopen($baseDir.'/.upload.lock','c');
if ($lockFp && !flock($lockFp, LOCK_EX)) { /* ไม่ล็อกได้ก็ไปต่อ */ }

$savedFiles = [];
$errors = [];

// ประมวลผลไฟล์แต่ละไฟล์
foreach ($processedFiles as $file) {
  $safe = preg_replace('/[^A-Za-z0-9._-]/','_', $file['original_name']);
  $dest = $baseDir . '/' . $safe;
  $tmp  = $baseDir . '/.' . $safe . '.part.' . uniqid('', true);

  // เขียนไปไฟล์ชั่วคราวก่อน
  if (!move_uploaded_file($file['tmp_name'], $tmp)) {
    $errorMsg = "Failed to move file: " . $file['original_name'];
    $errorMsg .= " (tmp: " . $file['tmp_name'] . ", dest: " . $tmp . ")";
    $errorMsg .= " - PHP Error: " . error_get_last()['message'] ?? 'unknown';
    $errors[] = $errorMsg;
    continue;
  }
  @chmod($tmp, 0664);

  // ลบทิ้งของเดิมแล้ว rename แบบอะตอมมิก
  @unlink($dest);
  if (!@rename($tmp, $dest)) {
    @unlink($tmp);
    $errors[] = "Failed to rename file: " . $file['original_name'];
    continue;
  }

  $savedFiles[] = [
    'original_name' => $file['original_name'],
    'saved_name' => $safe,
    'path' => $dest,
    'size_bytes' => @filesize($dest),
    'field' => $file['field']
  ];
}

if ($lockFp) { flock($lockFp, LOCK_UN); fclose($lockFp); }

// ส่งผลลัพธ์
if (!empty($errors)) {
  http_response_code(500);
  $p=json_encode([
    'ok'=>false,
    'error'=>'partial_upload_failed',
    'saved_files'=>$savedFiles,
    'errors'=>$errors
  ], JSON_UNESCAPED_SLASHES);
} else {
  $p=json_encode([
    'ok'=>true,
    'files'=>$savedFiles,
    'total_files'=>count($savedFiles)
  ], JSON_UNESCAPED_SLASHES);
}

header('Content-Length: '.strlen($p));
echo $p;
