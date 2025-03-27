<?php
class AleXmlReader
{
  public $xml,$categories,$products,$cats,$offers;

  public function __construct(string $xml_file)
  {
    if (!file_exists($xml_file)) {
      die("Файл не найден");
    }
    $xml = simplexml_load_file($xml_file);
    if (!$xml) {
      die("Ошибка загрузки YML");
    }
    if (!$xml->shop->offers) {
      die("Нет тега offers YML");
    }

    $this->xml = $xml;
    $this->offers = $xml->shop->offers->offer;
    $this->cats = $xml->shop->categories->category;
    $this->products = [];
    $this->categories = [];
  }

  public function parse_params($params_elem)
  {
    $params = [];
    foreach ($params_elem as $param) {
      $name = (string)$param['name'];
      $value = (string)$param;
      $params[$name] = $value;
    }
    return $params;
  }


  public function parse_categories()
  {
    foreach ($this->cats as $cat) {
      $id = (string)$cat['id'];
      $parent_id = (string)$cat['parentId'];
      $name = (string)$cat;
      $this->categories[$id] = 
      [
          'id' => $id,
          'parent_id' => $parent_id,
          'name' => $name,
      ];
    }
  }

  public function parse()
  {

    $this->parse_categories();

    foreach ($this->offers as $offer) {
      $offer_id = (string)$offer['id'];
      $price = (float)$offer->price;
      $name = (string)$offer->name;
      $xml_category_id = (string)$offer->categoryId;

      $pictures = (array)$offer->picture;
      $pictures = array_map('trim', $pictures);
      $description = trim((string)$offer->description);


      $params = $this->parse_params($offer->param);

      $this->products[$offer_id] = [
        'name' => $name,
        'description' => $description,
        'xml_category_id' => $xml_category_id,
        'price' => $price,
        'images' => $pictures,
        'offer_id' => $offer_id,
        'attributes' => $params,
      ];

    }
    return [
      $this->categories,
      $this->products,
    ];
  }
}
