<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace lembadm\randomfile;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;


class RandomBehavior extends Behavior
{
    /**
     * @var string Аттрибут который будет использоватся для сохранения пути к изображению
     */
    public $attribute;

    /**
     * @var array Массив сценариев в которых поведение должно срабатывать
     */
    public $scenarios = [];

    /**
     * @var string Путь к папке из которой будет производится выборка
     */
    public $path;

    /**
     * @var string Путь к папке в какую будет скопирован файл. Если null - не копировать
     */
    public $pathTo;

    /**
     * @var array Список разрешенных расширений файла
     * Пример:
     * ~~~
     * [ 'gif', 'gif' ]
     * ~~~
     * Расширение файла чувствительно к регистру символов.
     * По умолчанию null - любое расширение
     */
    public $extensions = ['jpeg', 'jpg', 'png'];

    /**
     * @var \yii\db\ActiveRecord
     */
    public $owner;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if ( ! $this->attribute or ! is_string($this->attribute) ) {
            throw new InvalidParamException("Invalid or empty \"{$this->attribute}\"");
        }

        if ( ! $this->path or ! is_string($this->path) ) {
            throw new InvalidParamException("Empty \"{$this->path}\".");
        }

        $this->path = FileHelper::normalizePath($this->path) . DIRECTORY_SEPARATOR;

        if($this->pathTo) {
            $this->pathTo = FileHelper::normalizePath($this->pathTo) . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Метод срабатывает в момент создания новой записи моедли.
     */
    public function beforeInsert()
    {
        $this->setAttribute();
    }

    /**
     * Метод срабатывает в момент обновления существующей записи моедли.
     */
    public function beforeUpdate()
    {
        $this->setAttribute();
    }

    /**
     * Метод срабатывает в момент удаления существующей записи моедли.
     */
    public function beforeDelete()
    {
        if ( $this->pathTo ) {

            $file = $this->pathTo . $this->owner->{$this->attribute};

            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function setAttribute()
    {
        if (in_array($this->owner->scenario, $this->scenarios)) {
            if ( empty($this->owner->{$this->attribute}) ) {
                $this->owner->{$this->attribute} = $this->getRandomFile();
            }
        }
    }

    private function getRandomFile()
    {
        $files = FileHelper::findFiles( $this->path, [ 'only' => $this->extensions ]);

        if( ! $files ) {
            throw new InvalidConfigException( 'Try get random file from empty directory: ' . $this->path );
        }

        $source = $files[ array_rand($files) ];

        if( $this->pathTo ) {
            $destination = uniqid() . '.' . pathinfo( $source, PATHINFO_EXTENSION );
            copy( $source, $this->pathTo . $destination );
        }
        else {
            $destination = pathinfo( $source, PATHINFO_BASENAME );
        }

        return $destination;
    }
}
