<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        static $i = 0;
        $i++;

        return [
            'title' => "Sample Book {$i}",
            'author' => "Author {$i}",
            'publisher' => "Publisher {$i}",
            'page' => 100 + $i,
            'isbn' => sprintf('978-00000000%02d', $i),
        ];
    }
}
