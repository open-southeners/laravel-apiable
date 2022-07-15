<?php

namespace OpenSoutheners\LaravelApiable\Tests\Helpers;

use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;

trait GeneratesPredictableTestData
{
    /**
     * Generate a new instance of this class with predictable data.
     *
     * @return $this
     */
    public function generateTestData()
    {
        User::insert([
            [
                'name' => 'Aysha',
                'email' => 'aysha@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
            [
                'name' => 'Ruben',
                'email' => 'd8vjork@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
            [
                'name' => 'Coco',
                'email' => 'coco@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
            [
                'name' => 'Perla',
                'email' => 'perla@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
            [
                'name' => 'Ruben',
                'email' => 'ruben_robles@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
        ]);

        Tag::insert([
            [
                'name' => 'Recipes',
                'slug' => 'recipes',
                'created_by' => 1,
            ],
            [
                'name' => 'Traveling',
                'slug' => 'traveling',
                'created_by' => 1,
            ],
            [
                'name' => 'Programming',
                'slug' => 'programming',
                'created_by' => 2,
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'created_by' => 2,
            ],
            [
                'name' => 'Tips',
                'slug' => 'tips',
                'created_by' => 2,
            ],
            [
                'name' => 'Internet',
                'slug' => 'internet',
                'created_by' => 2,
            ],
            [
                'name' => 'Games',
                'slug' => 'games',
                'created_by' => 2,
            ],
            [
                'name' => 'Computers',
                'slug' => 'computers',
                'created_by' => 3,
            ],
            [
                'name' => 'Pets',
                'slug' => 'pets',
                'created_by' => 4,
            ],
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
                'created_by' => 4,
            ],
        ]);

        Post::insert([
            [
                'title' => 'My first test',
                'content' => 'Hello this is my first test',
                'status' => 'Active',
                'author_id' => 1,
            ],
            [
                'title' => 'Hello world',
                'content' => 'A classic in programming...',
                'status' => 'Archived',
                'author_id' => 2,
            ],
            [
                'title' => 'Y esto en español',
                'content' => 'Porque si',
                'status' => 'Active',
                'author_id' => 3,
            ],
            [
                'title' => 'Hola mundo',
                'content' => 'Lorem ipsum',
                'status' => 'Inactive',
                'author_id' => 3,
            ],
        ]);

        Post::find(1)->tags()->attach([1, 3, 4]);
        Post::find(2)->tags()->attach([1, 3, 4, 5]);
        Post::find(3)->tags()->attach(5);

        return $this;
    }
}
