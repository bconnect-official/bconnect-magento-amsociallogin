<?php

declare(strict_types=1);

namespace Bconnect\Amsociallogin\Plugin;

use Amasty\SocialLogin\Model\SocialList;
use Bconnect\Amsociallogin\Provider\Config;

/**
 * @author  Agence Dn'D <contact@dnd.fr>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.dnd.fr/
 */
class AddBConnectToAmastySocialList
{
    public function afterGetList(SocialList $subject, array $result): array
    {
        $result[Config::AMASTY_TYPE] = Config::AMASTY_NAME;
        return $result;
    }
}
