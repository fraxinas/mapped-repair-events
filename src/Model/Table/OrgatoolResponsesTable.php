<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class OrgatoolResponsesTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setPrimaryKey([
            'workshop_uid',
            'user_uid',
            'event_uid'
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_uid'
        ]);
        $this->belongsTo('Workshops', [
            'foreignKey' => 'workshop_uid'
        ]);
        $this->belongsTo('Events', [
            'foreignKey' => 'event_uid'
        ]);
    }    
}

?>