<?php
namespace app\widgets;

use Yii;
use yii\base\Widget;

class ModalWidget extends Widget
{
    public $modalParams;

    public function run()
    {
        return $this->render('@app/views/widgets/modal', ['params' => $this->modalParams]);
    }
}
