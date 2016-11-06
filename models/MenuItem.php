<?php

namespace infoweb\menu\models;

use Yii;
use yii\validators;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use creocoder\translateable\TranslateableBehavior;
use infoweb\pages\models\Page;

/**
 * This is the model class for table "menu_item".
 *
 * @property string $id
 * @property string $menu_id
 * @property string $parent_id
 * @property string $entity
 * @property string $entity_id
 * @property string $level
 * @property string $name
 * @property string $url
 * @property integer $position
 * @property integer $active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Menus $menu
 */
class MenuItem extends \yii\db\ActiveRecord
{
    // Entity types
    const ENTITY_PAGE = 'page';
    const ENTITY_URL = 'url';
    const ENTITY_MENU_ITEM = 'menu-item';
    const ENTITY_NONE = 'none';

    const TYPE_SYSTEM = 'system';
    const TYPE_USER_DEFINED = 'user-defined';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'menu_item';
    }

    public function behaviors()
    {
        return [
            'translateable' => [
                'class' => TranslateableBehavior::className(),
                'translationAttributes' => ['name', 'params'],
            ],
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => function() { return time(); },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['menu_id', 'active', 'parent_id', 'level', 'position', 'public', 'created_at', 'updated_at'], 'integer'],
            ['public', 'default', 'value' => Yii::$app->getModule('menu')->defaultPublicVisibility],
            [['url', 'anchor', 'type'], 'string', 'max' => 255],
            // Required
            [['menu_id', 'parent_id', 'entity'], 'required'],
            // Only required when the entity is no url
            [['entity_id'], 'required', 'when' => function($model) {
                return !in_array($model->entity , [self::ENTITY_URL, self::ENTITY_NONE]);
            }],
            // Trim
            [['url', 'anchor'], 'trim'],
            [['url'], 'required', 'when' => function($model) {
                return $model->entity == self::ENTITY_URL;
            }],
            [['url'], 'url', 'defaultScheme' => 'http'],
            ['active', 'default', 'value' => 1],
            [['entity_id'], 'default', 'value' => 0],
            ['parent_id', function($attribute, $params) {
                if (!empty($this->parent_id) && $this->level > $this->menu->max_level - 1)
                    $this->addError($attribute, Yii::t('infoweb/menu', 'The maximum level has been reached'));
            }]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'menu_id' => Yii::t('infoweb/menu', 'Menu ID'),
            'parent_id' => Yii::t('infoweb/menu', 'Parent ID'),
            'entity' => Yii::t('app', 'Entity'),
            'entity_id' => Yii::t('app', 'Entity ID'),
            'level' => Yii::t('infoweb/menu', 'Level'),
            'name' => Yii::t('app', 'Name'),
            'url' => Yii::t('app', 'Url'),
            'anchor' => Yii::t('infoweb/menu', 'Anchor'),
            'position' => Yii::t('app', 'Position'),
            'active' => Yii::t('app', 'Active'),
            'public' => Yii::t('infoweb/menu', 'Public'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenu()
    {
        return $this->hasOne(Menu::className(), ['id' => 'menu_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTranslations()
    {
        return $this->hasMany(MenuItemLang::className(), ['menu_item_id' => 'id']);
    }

    /**
     * Returns the model of the entity that is associated with the item
     *
     * @return  mixed
     */
    public function getEntityModel()
    {
        switch ($this->entity) {

            case self::ENTITY_URL:
                return self::findOne($this->id);
                break;

            default:
                $className = $this->entity;
                return $className::findOne($this->entity_id);
                break;
        }
    }

    /**
     * Returns the url for the item
     *
     * @param   boolean     A flag to determine if the language parameter should
     *                      be added to the url
     * @param   boolean     A flag to determine if the url should be prefixed with
     *                      the webpath
     * @return  string
     */
    public function getUrl($includeLanguage = true, $excludeWebPath = false)
    {
        // Url
        if ($this->entity == self::ENTITY_NONE) {
            return null;
        } elseif ($this->entity == self::ENTITY_URL) {
            return $this->url;
        } else {
            $prefix = (!$excludeWebPath) ? '@web/' : '';
            $prefix .= ($includeLanguage) ? Yii::$app->language.'/' : '';

            // Page
            if ($this->entity == Page::className()) {
                $page = $this->getEntityModel();

                // In the frontend application, the alias for the homepage is ommited
                // and '/' is used
                if (Yii::$app->id == 'app-frontend' && $page->homepage == true) {
                    return Url::to($prefix);
                }

                $url = "{$prefix}{$page->alias->url}";

                // Params are set, append to the url
                if (!empty($this->params)) {
                    $url = $url . $this->params;
                }

                // An anchor is set, append it to the url
                if (!empty($this->anchor)) {
                    return Url::to("{$url}#{$this->anchor}");
                }

                return Url::to($url);

                // Everything else
            } else {
                // Second parameter is language
                $url = $this->getEntityModel()->getUrl($includeLanguage, null, $excludeWebPath);

                // Params are set, append to the url
                if (!empty($this->params)) {
                    $url = $url . $this->params;
                }

                return $url;
            }
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(MenuItem::className(), ['parent_id' => 'id']);
    }

    /**
     * Parent menu item
     * @return null|static
     */
    public function getParent()
    {
        return $this->hasOne(MenuItem::className(), ['id' => 'parent_id']);
    }

    /**
     * Returns a recursive list of all parents of the item
     *
     * @param   int|null    The id of the item for which the parents have to be loaded.
     *                      When null is passed, the id of the loaded MenuItem instance is taken.
     */
    public function getParents($id = null, $parents = [])
    {
        if ($id == null) {
            $item = $this;
        } else {
            $item = MenuItem::findOne($id);
        }

        if ($item->parent) {
            $parents[] = $item->parent;

            return $this->getParents($item->parent->id, $parents);
        }

        return $parents;
    }

    /**
     * Get the next position
     *
     * @return int
     */
    public function nextPosition()
    {
        $result = (new Query)
                    ->select([
                        'position' => 'MAX(position)'
                    ])
                    ->from($this->tableName())
                    ->where(['level' => $this->level, 'parent_id' => $this->parent_id, 'menu_id' => $this->menu_id])
                    ->scalar();
        if (!$result) {
            $result = 1;
        }

        return $result;
    }

    /**
     * Recursively deletes all children of the item
     *
     * @return  boolean
     */
    public function deleteChildren()
    {
        foreach ($this->getChildren()->all() as $child) {
            if (!$child->delete())
                return false;
        }

        return true;
    }

    /**
     * Returns a tree of all items, grouped by menu, formatted for usage in a
     * Html::dropDownList widget
     *
     * @return  array
     */
    public function getAllForDropDownList($language = null)
    {
        $language = ($language) ?: Yii::$app->language;

        $items = [];

        foreach (Menu::find()->all() as $menu) {
            $items[$menu->name] = $menu->getAllForDropDownList(0, $language);
        }

        return $items;
    }

    /**
     * Returns all children formatted for usage in a Html::dropDownList widget:
     *      [
     *          'id' => 'name',
     *          'id' => 'name,
     *          ...
     *      ]
     *
     * @return  array
     */
    public function getChildrenForDropDownList($items, $language = null)
    {
        $language = ($language) ?: Yii::$app->language;

        foreach ($this->getChildren()->orderBy(['position' => SORT_ASC])->all() as $child) {
            // Prepend the name for indentation
            $prepend = str_repeat('-', ($child->level) ? $child->level * 2 : $child->level);
            $prepend .= ($child->level) ? '> ' : '';
            $items[$child->id] = "{$prepend}{$child->getTranslation($language)->name}";

            if ($child->children) {
                $items = $child->getChildrenForDropDownList($items, $language);
            }
        }

        return $items;
    }
}
