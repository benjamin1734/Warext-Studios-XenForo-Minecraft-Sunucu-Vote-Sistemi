<?php

namespace Warext\MinecraftVote\Admin\Controller;

use Warext\MinecraftVote\Entity\Category as CategoryEntity;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Category extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        if ($this->isPost())
        {
            $category = $this->em()->create('Warext\MinecraftVote:Category');
            $this->applyInput($category);
            $category->save();

            return $this->redirect(
                $this->buildLink('warext-minecraft/categories'),
                'Kategori oluşturuldu.'
            );
        }

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->order('display_order', 'ASC')
            ->order('category_id', 'ASC')
            ->fetch();

        $usageRows = $this->db()->fetchPairs(
            'SELECT category_id, COUNT(*) FROM xf_warext_mc_server_category GROUP BY category_id'
        );
        $usageCounts = [];
        foreach ($categories as $category)
        {
            $usageCounts[$category->category_id] = (int)($usageRows[$category->category_id] ?? 0);
        }

        return $this->view('Warext\MinecraftVote:Category\Index', 'warext_mc_admin_category_index', [
            'categories' => $categories,
            'usageCounts' => $usageCounts
        ]);
    }

    public function actionEdit(ParameterBag $params)
    {
        $category = $this->assertCategoryExists((int)$params->category_id);

        if ($this->isPost())
        {
            $this->applyInput($category);
            $category->save();

            return $this->redirect(
                $this->buildLink('warext-minecraft/categories'),
                'Kategori güncellendi.'
            );
        }

        return $this->view('Warext\MinecraftVote:Category\Edit', 'warext_mc_admin_category_edit', [
            'category' => $category
        ]);
    }

    public function actionToggle(ParameterBag $params)
    {
        if (!$this->isPost())
        {
            return $this->redirect($this->buildLink('warext-minecraft/categories'));
        }

        $category = $this->assertCategoryExists((int)$params->category_id);
        $category->is_active = !$category->is_active;
        $category->save();

        return $this->redirect($this->buildLink('warext-minecraft/categories'));
    }

    public function actionDelete(ParameterBag $params)
    {
        if (!$this->isPost())
        {
            return $this->redirect($this->buildLink('warext-minecraft/categories'));
        }

        $category = $this->assertCategoryExists((int)$params->category_id);
        $this->db()->delete('xf_warext_mc_server_category', 'category_id = ?', $category->category_id);
        $category->delete();

        return $this->redirect(
            $this->buildLink('warext-minecraft/categories'),
            'Kategori silindi.'
        );
    }

    protected function applyInput(CategoryEntity $category): void
    {
        $input = $this->filter([
            'title' => 'str',
            'slug' => 'str',
            'description' => 'str',
            'display_order' => 'uint',
            'is_active' => 'bool'
        ]);

        $title = trim($input['title']);
        if ($title === '')
        {
            $category->error('Kategori adı boş bırakılamaz.', 'title');
        }

        $slug = trim($input['slug']);
        if ($slug === '')
        {
            $slug = $this->slugify($title);
        }
        else
        {
            $slug = $this->slugify($slug);
        }

        $existing = $this->finder('Warext\MinecraftVote:Category')
            ->where('slug', $slug)
            ->fetchOne();
        if ($existing && (int)$existing->category_id !== (int)$category->category_id)
        {
            $category->error('Bu kategori kısa adı zaten kullanılıyor.', 'slug');
        }

        $category->title = mb_substr($title, 0, 50);
        $category->slug = $slug;
        $category->description = mb_substr(trim($input['description']), 0, 255);
        $category->display_order = max(1, (int)$input['display_order']);
        $category->is_active = (bool)$input['is_active'];
    }

    protected function slugify(string $value): string
    {
        $value = trim($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && $ascii !== '')
        {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return substr($value ?: 'kategori', 0, 50);
    }

    protected function assertCategoryExists(int $categoryId): CategoryEntity
    {
        $category = $this->em()->find('Warext\MinecraftVote:Category', $categoryId);
        if (!$category)
        {
            throw $this->exception($this->notFound());
        }

        return $category;
    }
}
