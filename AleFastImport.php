<?php
require_once('AleCatsImport.php');
require_once('AleImagesImport.php');
require_once('AleUtils.php');
class AleFastImport
{
  public function __construct()
  {
    $this->images_import = new AleImagesImport();
    $this->u = new AleUtils();
    $this->cats_importer = new AleCatsImport();
    $this->inserted_parent_products_ids = [];
  }

  function import_attributes($product,$product_id){
    $attributes = $product['attributes'];
    $product_attributes = [];
    $i = 0;
    foreach ($attributes as $attr_name => $term) {
      $product_attributes[$attr_name] =  [
        'name'         => $attr_name,
        'value'        => $term,
        'position'     => $i++,
        'is_visible'   => 1,
        'is_variation' => 0,
        'is_taxonomy'  => 0,
      ];
    }
    update_post_meta($product_id, '_product_attributes', $product_attributes);
  }

  function import_product($product) {
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

      [
          "first_image_id" => $first_image_id,
          "gallery_ids" => $gallery_ids,
      ] = $this->images_import->import($product['images']);

      $wc_product->set_image_id($first_image_id);
      if (!empty($gallery_ids)) {
          $wc_product->set_gallery_image_ids($gallery_ids);
      }

      $product_id = $wc_product->save();

      $this->import_attributes($product, $product_id);
      return $product_id;

  }

  function import_cats($products, $categories)
  {
    $imported_cats = $this->cats_importer->import($categories);
      foreach ($products as $offer_id => $product) {
        $xml_id = $product['xml_category_id'] ?? null;
        if ($xml_id) {
          $db_id = $imported_cats[$xml_id];
          $products[$offer_id]['db_category_id'] =  $db_id;
        }
    }

    return $products;
  }

  function import($categories, $products)
  {
    $totalItems = count($products);
    $processed = 0;
    $startTime = time();

    $products = $this->import_cats($products, $categories);
    foreach ($products as $offer_id => $product) {
      //drawbar 
      $processed++;
      $this->u->draw_progress_bar($processed, $totalItems, $startTime);
      ob_flush();
      flush();
      //import products
      $this->import_product($product);
    }
  }
}
