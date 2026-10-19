<?php

test('pagination shows the first, last and neighbouring pages with gaps between', function (int $current, int $last, array $pages) {
    expect(paginationPages($current, $last))->toBe($pages);
})->with([
    'single page' => [1, 1, [1]],
    'few pages' => [2, 3, [1, 2, 3]],
    'first page' => [1, 10, [1, 2, null, 10]],
    'middle page' => [5, 10, [1, null, 4, 5, 6, null, 10]],
    'last page' => [10, 10, [1, null, 9, 10]],
]);
