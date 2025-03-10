<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Component\Core\Model;

use Sylius\Component\Promotion\Model\CatalogPromotionScopeInterface as BaseCatalogPromotionScopeInterface;

interface CatalogPromotionScopeInterface extends BaseCatalogPromotionScopeInterface
{
    public const TYPE_FOR_VARIANTS = 'for_variants';

    public const TYPE_FOR_TAXONS = 'for_taxons';
}
