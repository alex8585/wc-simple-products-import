<?php
class AleCatsImport
{

  function import($categories_array)
  {
    $imported_terms = array();

    foreach ($categories_array as $category_data) {
      if (!isset($imported_terms[$category_data['id']])) {
        $parent_term_id = 0;

        // Если есть родитель и он уже импортирован - используем его ID
        if (!empty($category_data['parent_id']) && isset($imported_terms[$category_data['parent_id']])) {
          $parent_term_id = $imported_terms[$category_data['parent_id']];
        }

        // Проверяем, существует ли термин
        $existing_term = term_exists($category_data['name'], 'product_cat', $parent_term_id);

        if (!$existing_term) {
          // Создаём новую категорию
          $term_data = wp_insert_term(
            $category_data['name'],
            'product_cat',
            array('parent' => $parent_term_id)
          );

          if (!is_wp_error($term_data)) {
            $imported_terms[$category_data['id']] = $term_data['term_id'];
          }
        } else {
          // Обновляем родителя существующей категории
          $imported_terms[$category_data['id']] = $existing_term['term_id'];
          wp_update_term(
            $existing_term['term_id'],
            'product_cat',
            array('parent' => $parent_term_id)
          );
        }
      }
    }

    return $imported_terms;
  }
}
