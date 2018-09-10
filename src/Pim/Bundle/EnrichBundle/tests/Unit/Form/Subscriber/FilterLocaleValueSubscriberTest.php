<?php

namespace Pim\Bundle\EnrichBundle\tests\Unit\Form\Subscriber;

use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use PHPUnit\Framework\TestCase;
use Pim\Bundle\EnrichBundle\Form\Subscriber\FilterLocaleValueSubscriber;

/**
 * Test related class
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FilterLocaleValueSubscriberTest extends TestCase
{
    const CURRENT_LOCALE = 'fr_FR';
    const COMPARISON_LOCALE = 'fr_BE';
    const OTHER_LOCALE = 'en_US';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->target = new FilterLocaleValueSubscriber(self::CURRENT_LOCALE, self::COMPARISON_LOCALE);
    }

    /**
     * Test related method
     */
    public function testInstandOfEventSubscriberInterface()
    {
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->target);
    }

    /**
     * Test related method
     */
    public function testGetSubscribedEvents()
    {
        $this->assertEquals(['form.pre_set_data' => 'preSetData'], $this->target->getSubscribedEvents());
    }

    /**
     * Test related method
     */
    public function testPreSetDataWithNullData()
    {
        $form = $this->getFormMock();
        $event = $this->getEventMock(null, $form);

        $form->expects($this->never())
            ->method('remove');

        $this->target->preSetData($event);
    }

    /**
     * Test related method
     */
    public function testPreSetData()
    {
        $data = [
            'name_current'               => $this->getProductValueMock(
                'name_current',
                self::CURRENT_LOCALE
            ),
            'name_other'                 => $this->getProductValueMock(
                'name_other',
                self::OTHER_LOCALE
            ),
            'not_localizable_attribute' => $this->getProductValueMock('not_localized_attribute', null),
        ];

        $form = $this->getFormMock();
        $event = $this->getEventMock($data, $form);

        $form->expects($this->exactly(1))
            ->method('remove')
            ->with('name_other');

        $this->target->preSetData($event);
    }

    public function testSetComparisonAttributesDisabled()
    {
        $data = [
            'name_current'    => $this->getProductValueMock($this->getAttributeMock(), self::CURRENT_LOCALE),
            'name_comparison' => $this->getProductValueMock($this->getAttributeMock(), self::COMPARISON_LOCALE),
        ];

        $form = $this->getFormMock();
        $event = $this->getEventMock($data, $form);

        $form->expects($this->exactly(1))
            ->method('add')
            ->with(
                'name_comparison',
                'pim_product_value',
                [
                    'disabled'     => true,
                    'block_config' => [
                        'mode' => 'comparison'
                    ]
                ]
            );

        $this->target->preSetData($event);
    }

    /**
     * @param mixed $data
     * @param mixed $form
     *
     * @return \Symfony\Component\Form\FormEvent
     */
    private function getEventMock($data, $form)
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));

        $event->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        return $event;
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function getFormMock()
    {
        return $this
            ->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param mixed $attribute
     * @param mixed $locale
     *
     * @return ValueInterface
     */
    private function getProductValueMock($attributeCode, $locale)
    {
        $value = $this->createMock(ValueInterface::class);

        $value->expects($this->any())
            ->method('getAttributeCode')
            ->will($this->returnValue($attributeCode));

        $value->expects($this->any())
            ->method('getLocaleCode')
            ->will($this->returnValue($locale));

        if (null !== $locale) {
            $value->expects($this->any())
                ->method('isLocalizable')
                ->will($this->returnValue(true));
        } else {
            $value->expects($this->any())
                ->method('isLocalizable')
                ->will($this->returnValue(false));
        }

        return $value;
    }

    /**
     * @param bool $localizable
     *
     * @return AttributeInterface
     */
    private function getAttributeMock($localizable = true)
    {
        $attribute = $this->createMock(AttributeInterface::class);

        $attribute->expects($this->any())
            ->method('isLocalizable')
            ->will($this->returnValue($localizable));

        return $attribute;
    }
}
