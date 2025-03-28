<?php
require_once('AleCatsImport.php');
require_once('AleImagesImport.php');
require_once('AleUtils.php');

class AleFastImport
{
  private $images_import, $u, $cats_importer;
  private $execution_times = [];
  public function __construct()
  {
    $this->images_import = new AleImagesImport();
    $this->u = new AleUtils();
    $this->cats_importer = new AleCatsImport();
  }

  private function startTimer(): float
  {
    return microtime(true);
  }

  private function recordTime(string $function_name, float $start_time): void
  {
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // в миллисекундах
    $this->execution_times[$function_name][] = $execution_time;
  }

  public function getPerformanceStats(): array
  {
    $stats = [];
    foreach ($this->execution_times as $function => $times) {
      $stats[$function] = [
        'count' => count($times),
        'total_time' => round(array_sum($times), 2) . 'ms',
        'avg_time' => round(array_sum($times) / count($times), 2) . 'ms',
        'max_time' => round(max($times), 2) . 'ms',
        'min_time' => round(min($times), 2) . 'ms'
      ];
    }
    return $stats;
  }

  function import_attributes($product, $product_id)
  {
    $start = $this->startTimer();

    $attributes = $product['attributes'];
    $product_attributes = [];
    $i = 0;
    foreach ($attributes as $attr_name => $term) {
      $product_attributes[$attr_name] = [
        'name' => $attr_name,
        'value' => $term,
        'position' => $i++,
        'is_visible' => 1,
        'is_variation' => 0,
        'is_taxonomy' => 0,
      ];
    }
    update_post_meta($product_id, '_product_attributes', $product_attributes);

    $this->recordTime(__FUNCTION__, $start);
  }

  function import_product($product)
  {
    $start = $this->startTimer();

    $offer_id = $product['offer_id'];
    $name = $product['name'];
    $cat_id = $product['db_category_id'];
    $description = $product['description'];
    $price = $product['price'];
    $regular_price = $product['regular_price'] ?? $price;

    $wc_product = new WC_Product_Simple();
    $wc_product->set_name($name);
    $wc_product->set_sku($offer_id);
    $wc_product->set_manage_stock(false);
    $wc_product->set_status('publish');
    $wc_product->set_category_ids(array($cat_id));
    $wc_product->set_description($description);
    $wc_product->set_price($price);
    $wc_product->set_regular_price($regular_price);

    $s = $this->startTimer();
    [
      "first_image_id" => $first_image_id,
      "gallery_ids" => $gallery_ids,
    ] = $this->images_import->import($product['images']);

    $this->recordTime('IMAGES', $s);

    $wc_product->set_image_id($first_image_id);
    if (!empty($gallery_ids)) {
      $wc_product->set_gallery_image_ids($gallery_ids);
    }

    $product_id = $wc_product->save();
    $this->import_attributes($product, $product_id);

    $this->recordTime(__FUNCTION__, $start);
    return $product_id;
  }

  function import_cats($products, $categories)
  {
    $start = $this->startTimer();

    $imported_cats = $this->cats_importer->import($categories);
    foreach ($products as $offer_id => $product) {
      $xml_id = $product['xml_category_id'] ?? null;
      if ($xml_id) {
        $db_id = $imported_cats[$xml_id];
        $products[$offer_id]['db_category_id'] = $db_id;
      }
    }

    $this->recordTime(__FUNCTION__, $start);
    return $products;
  }

  function import($categories, $products)
  {
    ob_start();
    $start = $this->startTimer();

    $totalItems = count($products);
    $processed = 0;
    $startTime = time();

    $products = $this->import_cats($products, $categories);
    foreach ($products as $offer_id => $product) {
      $processed++;
      $this->u->draw_progress_bar($processed, $totalItems, $startTime);
      ob_flush();
      flush();
      $this->import_product($product);
    }

    $this->recordTime(__FUNCTION__, $start);

    // Выводим статистику в консоль
    $stats = $this->getPerformanceStats();
    echo "\n\n=== PERFORMANCE STATISTICS ===\n";
    foreach ($stats as $function => $data) {
      echo sprintf(
        "%s:\n  Called: %d times\n  Total: %s\n  Avg: %s\n  Max: %s\n  Min: %s\n\n",
        $function,
        $data['count'],
        $data['total_time'],
        $data['avg_time'],
        $data['max_time'],
        $data['min_time']
      );
    }
  }
}
