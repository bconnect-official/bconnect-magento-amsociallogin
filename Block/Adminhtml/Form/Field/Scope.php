<?php

declare(strict_types=1);

namespace Bconnect\Amsociallogin\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * @author  Agence Dn'D <contact@dnd.fr>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.dnd.fr/
 */
class Scope extends AbstractFieldArray
{
    /**
     * @inheritDoc
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('scope_value', [
            'label' => __('Scope Value'),
            'class' => 'required-entry',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Scope');
    }
}
