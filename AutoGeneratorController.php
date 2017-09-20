<?php
/**
 * This file is part of the hamvorinf/gii-autocode package.
 *
 * (c) Hamvorinf <hamvorinf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace hamvorinf\giiauto;

use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\db\Connection;

class AutoGeneratorController extends Controller
{
    /**
     * @var string Yii project path
     */
    public $yiiPath = 'yii';

    /**
     * @var Connection|string Database connection
     */
    public $db = 'db';

    /**
     * @var string Database connection name
     */
    private $dbName = 'db';

    /**
     * @var boolean whether the strings will be generated using `Yii::t()` or normal strings.
     */
    public $enableI18N = true;

    /**
     * @var boolean whether to wrap the `GridView` or `ListView` widget with the `yii\widgets\Pjax` widget
     */
    public $enablePjax = false;

    /**
     * @var bool whether generate crud controller after generated model class
     */
    public $generateCrud = false;

    /**
     * @var string the message category used by `Yii::t()` when `$enableI18N` is `true`.
     * Defaults to `app`.
     */
    public $messageCategory = 'app';

    /**
     * @var array Model generator config
     */
    public $modelConfig;

    /**
     * @var array Model generator default config
     */
    private $defaultModelConfig = [
        'ns' => 'common\\\\models\\\\base',             // model namespace
        'nsSearch' => 'common\\\\models\\\\search',     // model search namespace
        'baseClass' => 'yii\\\\db\\\\ActiveRecord',     // model template class
        'useTablePrefix' => 1,                          // whether use table prefix
        'generateLabelsFromComments' => 1,              // whether use comments of tables as label annotations
        'overwrite' => true,                            // overwrite all existed file
        'codeTemplate' => 'default',                    // default code template
    ];

    /**
     * @var array Controller generator config
     */
    public $controllerConfig;

    /**
     * @var array Controller generator default config
     */
    public $standarControllerConfig = [
        'ns' => 'backend\\\\controller',                // controller namespace
        'viewPath' => '@backend/views',                 // view file path
        'baseClass' => 'yii\\\\web\\\\Controller',      // controller template class
        'overwrite' => true,                            // overwrite all existed file
        'codeTemplate' => 'default',                    // default code template
    ];


    /**
     * Initialization
     * @throws InvalidConfigException
     */
    public function init()
    {
        // check db connection
        if (is_string($this->db)) {
            $this->dbName = $this->db;
            $this->db = Yii::$app->get($this->db);
        }

        // check yii project path
        $this->yiiPath = realpath($this->yiiPath);
        if($this->yiiPath === false){
            throw new InvalidConfigException('Wrong path: '.$this->yiiPath.'. Please set the absolute path.');
        }

        // check model config
        foreach($this->defaultModelConfig as $k => $v) {
            if(!isset($this->modelConfig[$k])) {
                $this->modelConfig[$k] = $v;
            }
        }

        // check controller config
        foreach($this->standarControllerConfig as $k => $v) {
            if(!isset($this->controllerConfig[$k])) {
                $this->controllerConfig[$k] = $v;
            }
        }

        parent::init();
    }

    /**
     * Run
     * @return int
     */
    public function actionIndex()
    {
        $output = null;
        $return = null;
        $tables = $this->db->createCommand('show tables')->queryAll();
        if (count($tables) == 0) {
            echo "No any tables.\n";
            return 0;
        }

        if(is_dir('backend/models/search') === false) {
            mkdir('backend/models/search', 0777);
        }

        foreach ($tables as &$table) {
            $table = current($table);
            $modelClass = str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace($this->db->tablePrefix, '', $table))));
            $cmdModel = 'php ' . $this->yiiPath . ' gii/model '
                . '--tableName=' . $table . ' '
                . '--modelClass=' . $modelClass . ' '
                . '--db=' . $this->dbName . ' '
                . '--ns=' . $this->modelConfig['ns'] . ' '
                . '--useTablePrefix=' . $this->modelConfig['useTablePrefix'] . ' '
                . '--generateLabelsFromComments=' . $this->modelConfig['generateLabelsFromComments'] . ' '
                . '--baseClass=' . $this->modelConfig['baseClass'] . ' '
                . '--enableI18N=' . $this->enableI18N . ' '
                . '--messageCategory=' . $this->messageCategory . ' '
                . '--template=' . $this->modelConfig['codeTemplate'];

            echo "\n\n===> Generoting Model " . $modelClass . " with Overwrite=" . ($this->modelConfig['overwrite'] ? '"ya"' : '"na"') . " Begin <===\n". $cmdModel . "\n";
            exec($cmdModel . '<<< ' . ($this->modelConfig['overwrite'] ? '"ya"' : '"na"'), $output, $return);
            $this->showLogs($output);


            if($this->generateCrud) {
                $controllerClass = $modelClass . 'Controller';
                $viewPath = $this->controllerConfig['viewPath'] . '/' . strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($modelClass)));
                $searchModelClass = $modelClass . 'Search';
                $cmdController = 'php ' . $this->yiiPath . ' gii/crud '
                    . '--modelClass=' . $this->modelConfig['ns'] . '\\\\' . $modelClass . ' '
                    . '--searchModelClass=' . $this->modelConfig['nsSearch'] . '\\\\' . $searchModelClass . ' '
                    . '--controllerClass=' . $this->controllerConfig['ns'] . '\\\\' . $controllerClass . ' '
                    . '--viewPath=' . $viewPath . ' '
                    . '--baseControllerClass=' . $this->controllerConfig['baseClass'] . ' '
                    . '--enableI18N=' . $this->enableI18N . ' '
                    . '--messageCategory=' . $this->messageCategory . ' '
                    . '--template=' . $this->controllerConfig['codeTemplate'] . ' '
                    . '--enablePjax=' . $this->enablePjax;

                echo "\n===> Generoting CRUD Controller " . $controllerClass . " with Overwrite=" . ($this->controllerConfig['overwrite'] ? '"ya"' : '"na"') . " Begin <===\n" . $cmdController . "\n";
                exec($cmdController . '<<< ' . ($this->controllerConfig['overwrite'] ? '"ya"' : '"na"'), $output, $return);
                $this->showLogs($output);
            }


            unset($table, $modelClass, $output, $return);
        }

        echo "Generate done!\n";
        return 0;
    }

    private function showLogs($output) {
        foreach ($output as $item) {
            if (empty($item)) {
                continue;
            }
            echo $item . "\n";
        }
    }
} 