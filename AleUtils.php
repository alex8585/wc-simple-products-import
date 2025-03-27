<?php
class AleUtils
{

  function draw_progress_bar($current, $total, $startTime, $barLength = 50)
  {
    // Защита от деления на ноль и некорректных значений
    if ($total <= 0 || $current < 0) {
      echo "\r[No progress: total items = $total]";
      return;
    }

    $progress = ($current / $total);
    $filled = floor($progress * $barLength);
    $empty = $barLength - $filled;

    $progressBar = '[' . str_repeat('=', $filled) . '>' . str_repeat(' ', $empty) . ']';
    $percent = number_format($progress * 100, 2);

    $elapsed = time() - $startTime;
    $elapsedStr = gmdate("H:i:s", $elapsed);

    if ($current > 0 && $elapsed > 0) {  // Защита от деления на ноль в скорости
      $eta = $elapsed / $current * ($total - $current);
      $etaStr = gmdate("H:i:s", $eta);
      $itemsPerSec = $current / $elapsed;
      $speed = number_format($itemsPerSec, 2) . " items/sec";
    } else {
      $etaStr = "N/A";
      $speed = "N/A";
    }

    echo sprintf(
      "\r%s %6.2f%% | Обработано: %d/%d | Время: %s | ETA: %s | Скорость: %s",
      $progressBar,
      $percent,
      $current,
      $total,
      $elapsedStr,
      $etaStr,
      $speed
    );

    if ($current == $total) {
      echo PHP_EOL . "Обработка завершена за $elapsedStr!" . PHP_EOL;
    }
  }
  function lower_encode($str)
  {
    $lc = mb_strtolower($str, 'UTF-8');
    $enc = urlencode($lc);
    return mb_strtolower($enc, 'UTF-8');
  }

  function ale_slug($string)
  {
    $replace = [
      'а' => 'a',
      'б' => 'b',
      'в' => 'v',
      'г' => 'g',
      'д' => 'd',
      'е' => 'e',
      'ё' => 'yo',
      'ж' => 'zh',
      'з' => 'z',
      'и' => 'i',
      'й' => 'y',
      'к' => 'k',
      'л' => 'l',
      'м' => 'm',
      'н' => 'n',
      'о' => 'o',
      'п' => 'p',
      'р' => 'r',
      'с' => 's',
      'т' => 't',
      'у' => 'u',
      'ф' => 'f',
      'х' => 'h',
      'ц' => 'ts',
      'ч' => 'ch',
      'ш' => 'sh',
      'щ' => 'shch',
      'ъ' => '',
      'ы' => 'y',
      'ь' => '',
      'э' => 'e',
      'ю' => 'yu',
      'я' => 'ya',
    ];
    $string = mb_strtolower($string);
    $string = strtr($string, $replace);
    return sanitize_title($string);
  }

  function name_to_pa($attr_name)
  {
    $attr_slag = $this->ale_slug($attr_name);
    return  'pa_' . $attr_slag;
  }

  public function __construct() {}
}
