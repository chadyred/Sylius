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

namespace Sylius\Component\Promotion\Model;

use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class CatalogPromotion implements CatalogPromotionInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
        getTranslation as private doGetTranslation;
    }

    /** @var mixed */
    protected $id;

    protected ?string $name = null;

    protected ?string $code = null;

    /**
     * @var Collection|CatalogPromotionScopeInterface[]
     *
     * @psalm-var Collection<array-key, CatalogPromotionScopeInterface>
     */
    protected Collection $scopes;

    protected Collection $actions;

    protected ?bool $enabled = true;

    public function __construct()
    {
        $this->initializeTranslationsCollection();

        $this->scopes = new ArrayCollection();
        $this->actions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): ?string
    {
        return $this->getTranslation()->getLabel();
    }

    public function setLabel(?string $label): void
    {
        $this->getTranslation()->setLabel($label);
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation()->getDescription();
    }

    public function setDescription(?string $description): void
    {
        $this->getTranslation()->setDescription($description);
    }

    /** @return CatalogPromotionTranslationInterface */
    public function getTranslation(?string $locale = null): TranslationInterface
    {
        /** @var CatalogPromotionTranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    protected function createTranslation(): CatalogPromotionTranslationInterface
    {
        return new CatalogPromotionTranslation();
    }

    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function hasScope(CatalogPromotionScopeInterface $scope): bool
    {
        return $this->scopes->contains($scope);
    }

    public function addScope(CatalogPromotionScopeInterface $scope): void
    {
        if (!$this->hasScope($scope)) {
            $scope->setCatalogPromotion($this);
            $this->scopes->add($scope);
        }
    }

    public function removeScope(CatalogPromotionScopeInterface $scope): void
    {
        $scope->setCatalogPromotion(null);
        $this->scopes->removeElement($scope);
    }

    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function hasAction(CatalogPromotionActionInterface $action): bool
    {
        return $this->actions->contains($action);
    }

    public function addAction(CatalogPromotionActionInterface $action): void
    {
        if (!$this->hasAction($action)) {
            $action->setCatalogPromotion($this);
            $this->actions->add($action);
        }
    }

    public function removeAction(CatalogPromotionActionInterface $action): void
    {
        $action->setCatalogPromotion(null);
        $this->actions->removeElement($action);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function enable(): void
    {
        $this->setEnabled(true);
    }

    public function disable(): void
    {
        $this->setEnabled(false);
    }
}
