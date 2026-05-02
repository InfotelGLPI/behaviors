<?php

namespace GlpiPlugin\Behaviors\Tests\Integration;

use GlpiPlugin\Behaviors\Config;
use Glpi\Tests\DbTestCase;

class ConfigTest extends DbTestCase
{
    public function testGetInstanceReturnsSingleton(): void
    {
        $a = Config::getInstance();
        $b = Config::getInstance();

        $this->assertSame($a, $b);
    }

    public function testConfigRecordExistsAfterInstall(): void
    {
        $config = Config::getInstance();

        $this->assertTrue($config->getFromDB(1));
        $this->assertSame(1, (int) $config->getID());
    }

    public function testAllBooleanFieldsDefaultToZero(): void
    {
        $boolFields = [
            'use_requester_item_group',
            'use_requester_user_group',
            'is_ticketsolutiontype_mandatory',
            'is_ticketsolution_mandatory',
            'is_ticketcategory_mandatory',
            'is_tickettaskcategory_mandatory',
            'is_tickettech_mandatory',
            'is_tickettechgroup_mandatory',
            'is_ticketrealtime_mandatory',
            'is_ticketlocation_mandatory',
            'is_ticketdate_locked',
            'use_assign_user_group',
            'ticketsolved_updatetech',
            'is_problemsolutiontype_mandatory',
            'add_notif',
            'addfup_updatetech',
            'is_tickettasktodo',
            'is_problemtasktodo',
            'is_changetasktodo',
        ];

        $config = Config::getInstance();
        $config->getFromDB(1);

        foreach ($boolFields as $field) {
            $this->assertSame(
                0,
                (int) $config->getField($field),
                "Le champ '$field' devrait valoir 0 par défaut."
            );
        }
    }

    public function testGetFieldReturnsUpdatedValueAfterModification(): void
    {
        $this->login();

        $config = Config::getInstance();
        $config->fields['is_ticketsolutiontype_mandatory'] = 1;

        $this->assertSame(1, (int) $config->getField('is_ticketsolutiontype_mandatory'));

        // Restaurer
        $config->fields['is_ticketsolutiontype_mandatory'] = 0;
    }
}
