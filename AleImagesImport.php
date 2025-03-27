<?php
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  require_once(ABSPATH . 'wp-admin/includes/file.php');
  require_once(ABSPATH . 'wp-admin/includes/media.php');

class AleImagesImport {

  function extension_from_url($url,$image_data) {
      $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
      $extension = strtok($extension, '?#');
      
      if($extension) {
        return $extension;
      }

      // Определяем тип изображения по сигнатуре
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime_type = $finfo->buffer($image_data);
      
      // Маппинг MIME-типов в расширения
      $mime_to_ext = [
          'image/jpeg' => 'jpg',
          'image/png' => 'png',
          'image/gif' => 'gif',
          'image/webp' => 'webp',
      ];

      if (!isset($mime_to_ext[$mime_type])) {
          return new WP_Error('invalid_mime', 'Неподдерживаемый тип изображения: ' . $mime_type);
      }

      return $mime_to_ext[$mime_type];
  }

  function upload_image_from_url($image_url, $post_id = 0, $description = '') {
      $image_url = trim($image_url);
      // Проверка URL
      if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
          return new WP_Error('invalid_url', 'Некорректный URL изображения');
      }

      // Получаем данные изображения
      $response = wp_remote_get($image_url, [
          'timeout' => 30,
          'redirection' => 5,
      ]);

      if (is_wp_error($response)) {
          return $response;
      }

      if (wp_remote_retrieve_response_code($response) !== 200) {
          return new WP_Error('http_error', 'Ошибка при загрузке изображения', [
              'status' => wp_remote_retrieve_response_code($response),
              'url' => $image_url
          ]);
      }

      // Получаем содержимое изображения
      $image_data = wp_remote_retrieve_body($response);
      if (empty($image_data)) {
          return new WP_Error('empty_content', 'Изображение пустое');
      }

      $extension = $this->extension_from_url($image_url,$image_data);

      $file_name = sanitize_file_name(basename(parse_url($image_url, PHP_URL_PATH))); // Добавлена закрывающая скобка
      if (empty(pathinfo($file_name, PATHINFO_EXTENSION))) {
          $file_name .= '.' . $extension;
      }
      // Создаем временный файл
      $tmp_file = wp_tempnam($file_name);
      if (!$tmp_file) {
          return new WP_Error('temp_fail', 'Ошибка создания временного файла');
      }

      // Сохраняем данные во временный файл
      file_put_contents($tmp_file, $image_data);

      // Подготавливаем данные для загрузки
      $file = [
          'name' => $file_name,
          'tmp_name' => $tmp_file,
          'size' => filesize($tmp_file),
          'error' => 0
      ];

      // Загружаем файл в медиатеку
      $attachment_id = media_handle_sideload($file, $post_id, $description);

      // Удаляем временный файл
      if (file_exists($tmp_file)) {
          unlink($tmp_file);
      }

      // Проверяем результат
      if (is_wp_error($attachment_id)) {
          return new WP_Error('upload_failed', 'Ошибка загрузки изображения', $attachment_id->get_error_message());
      }

      return $attachment_id;
  }

  function import($product_images)
  {
    $gallery_ids = [];
    $first_image_id = '';
    foreach ($product_images as $index => $image_url) {
      $image_id = $this->upload_image_from_url($image_url);
      if ($image_id) {
        if ($index === 0) {
          $first_image_id = $image_id;
        } else {
          $gallery_ids[] = $image_id;
        }
      }
    }
    return [
      'first_image_id' => $first_image_id,
      'gallery_ids' => $gallery_ids,
    ];
  }
}
