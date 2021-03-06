<?php

declare(strict_types=1);

namespace spec\Akeneo\Platform\Bundle\CatalogVolumeMonitoringBundle\Persistence\Query\Sql;

use Akeneo\Platform\Component\CatalogVolumeMonitoring\Volume\Query\AverageMaxQuery;
use Akeneo\Platform\Component\CatalogVolumeMonitoring\Volume\ReadModel\AverageMaxVolumes;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use PhpSpec\ObjectBehavior;
use Akeneo\Platform\Bundle\CatalogVolumeMonitoringBundle\Persistence\Query\Sql\AverageMaxScopableAttributesPerFamily;
use Prophecy\Argument;

class AverageMaxScopableAttributesPerFamilySpec extends ObjectBehavior
{
    function let(Connection $connection)
    {
        $this->beConstructedWith($connection, 14);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AverageMaxScopableAttributesPerFamily::class);
    }

    function it_is_an_average_and_max_query()
    {
        $this->shouldImplement(AverageMaxQuery::class);
    }

    function it_gets_average_and_max_volume($connection, Statement $statement)
    {
        $connection->query(Argument::type('string'))->willReturn($statement);
        $statement->fetch()->willReturn(['average' => '5', 'max' => '11']);
        $this->fetch()->shouldBeLike(new AverageMaxVolumes(11, 5, 14, 'average_max_scopable_attributes_per_family'));
    }
}
