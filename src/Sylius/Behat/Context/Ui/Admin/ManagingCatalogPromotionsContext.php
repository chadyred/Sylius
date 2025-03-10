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

namespace Sylius\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Element\Admin\CatalogPromotion\FormElementInterface;
use Sylius\Behat\Page\Admin\CatalogPromotion\CreatePageInterface;
use Sylius\Behat\Page\Admin\CatalogPromotion\UpdatePageInterface;
use Sylius\Behat\Page\Admin\Crud\IndexPageInterface;
use Sylius\Component\Core\Model\CatalogPromotionInterface;
use Sylius\Component\Core\Model\CatalogPromotionScopeInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class ManagingCatalogPromotionsContext implements Context
{
    private IndexPageInterface $indexPage;

    private CreatePageInterface $createPage;

    private UpdatePageInterface $updatePage;

    private FormElementInterface $formElement;

    public function __construct(
        IndexPageInterface $indexPage,
        CreatePageInterface $createPage,
        UpdatePageInterface $updatePage,
        FormElementInterface $formElement
    ) {
        $this->indexPage = $indexPage;
        $this->createPage = $createPage;
        $this->updatePage = $updatePage;
        $this->formElement = $formElement;
    }

    /**
     * @When I browse catalog promotions
     */
    public function iBrowseCatalogPromotions(): void
    {
        $this->indexPage->open();
    }

    /**
     * @When I want to create a new catalog promotion
     */
    public function iWantToCreateNewCatalogPromotion(): void
    {
        $this->createPage->open();
    }

    /**
     * @When I create a new catalog promotion with :code code and :name name
     */
    public function iCreateANewCatalogPromotionWithCodeAndName(string $code, string $name): void
    {
        $this->createPage->open();
        $this->createPage->specifyCode($code);
        $this->formElement->nameIt($name);
        $this->createPage->create();
    }

    /**
     * @When I create a new catalog promotion without specifying its code and name
     */
    public function iCreateANewCatalogPromotionWithoutSpecifyingItsCodeAndName(): void
    {
        $this->createPage->open();
        $this->createPage->create();
    }

    /**
     * @When I specify its code as :code
     */
    public function iSpecifyItsCodeAs(string $code): void
    {
        $this->createPage->specifyCode($code);
    }

    /**
     * @When I name it :name
     */
    public function iNameIt(string $name): void
    {
        $this->formElement->nameIt($name);
    }

    /**
     * @When I specify its label as :label in :localeCode
     */
    public function iSpecifyItsLabelAsIn(string $label, string $localeCode): void
    {
        $this->formElement->labelIt($label, $localeCode);
    }

    /**
     * @When I describe it as :description in :localeCode
     */
    public function iDescribeItAsIn(string $description, string $localeCode): void
    {
        $this->formElement->describeIt($description, $localeCode);
    }

    /**
     * @When I enable this catalog promotion
     */
    public function iEnableCatalogPromotion(): void
    {
        $this->formElement->changeEnableTo(true);
    }

    /**
     * @When I disable this catalog promotion
     */
    public function iDisableThisCatalogPromotion(): void
    {
        $this->formElement->changeEnableTo(false);
    }

    /**
     * @When I make it available in channel :channelName
     */
    public function iMakeItAvailableInChannel(string $channelName): void
    {
        $this->formElement->checkChannel($channelName);
    }

    /**
     * @When I make it unavailable in channel :channelName
     */
    public function iMakeItUnavailableInChannel(string $channelName): void
    {
        $this->formElement->uncheckChannel($channelName);
    }

    /**
     * @When /^I add(?:| another) scope that applies on ("[^"]+" variant)$/
     * @When /^I add scope that applies on ("[^"]+" variant) and ("[^"]+" variant)$/
     * @When /^I add scope that applies on variants ("[^"]+" variant) and ("[^"]+" variant)$/
     */
    public function iAddScopeThatAppliesOnVariants(ProductVariantInterface ...$variants): void
    {
        $variantCodes = array_map(function(ProductVariantInterface $variant) {
            return $variant->getCode();
        }, $variants);

        $this->formElement->addScope();
        $this->formElement->chooseLastScopeVariants($variantCodes);
    }

    /**
     * @When I remove its every action
     */
    public function iRemoveItsEveryAction(): void
    {
        $this->formElement->removeAllActions();
    }

    /**
     * @When I add another action that gives ":discount%" percentage discount
     * @When I add action that gives ":discount%" percentage discount
     */
    public function iAddActionThatGivesPercentageDiscount(string $discount): void
    {
        $this->formElement->addAction();
        $this->formElement->specifyLastActionDiscount($discount);
    }

    /**
     * @When I (try to) add it
     */
    public function iAddIt(): void
    {
        $this->createPage->create();
    }

    /**
     * @When I rename the :catalogPromotion catalog promotion to :name
     */
    public function iRenameTheCatalogPromotionTo(CatalogPromotionInterface $catalogPromotion, string $name): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);
        $this->formElement->nameIt($name);
        $this->updatePage->saveChanges();
    }

    /**
     * @When I modify a catalog promotion :catalogPromotion
     * @When I want to modify a catalog promotion :catalogPromotion
     */
    public function iWantToModifyACatalogPromotion(CatalogPromotionInterface $catalogPromotion): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);
    }

    /**
     * @When I save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->updatePage->saveChanges();
    }

    /**
     * @When /^I edit ("[^"]+" catalog promotion) to be applied on ("[^"]+" variant)$/
     */
    public function iEditCatalogPromotionToBeAppliedOn(CatalogPromotionInterface $catalogPromotion, ProductVariantInterface $productVariant): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);

        $this->formElement->chooseLastScopeVariants([$productVariant->getCode()]);
        $this->updatePage->saveChanges();
    }

    /**
     * @When I remove its every scope
     */
    public function iRemoveItsEveryScope(): void
    {
        $this->formElement->removeAllScopes();
    }

    /**
     * @When /^I edit ("[^"]+" catalog promotion) to have "([^"]+)%" discount$/
     */
    public function iEditCatalogPromotionToHaveDiscount(CatalogPromotionInterface $catalogPromotion, string $amount): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);
        $this->formElement->addAction();
        $this->formElement->specifyLastActionDiscount($amount);
        $this->updatePage->saveChanges();
    }

    /**
     * @When /^I disable ("[^"]*" catalog promotion)$/
     */
    public function iDisableCatalogPromotion(CatalogPromotionInterface $catalogPromotion): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);
        $this->formElement->changeEnableTo(false);
        $this->updatePage->saveChanges();
    }

    /**
     * @When /^I enable ("[^"]*" catalog promotion)$/
     */
    public function iEnableThisCatalogPromotion(CatalogPromotionInterface $catalogPromotion): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);
        $this->formElement->changeEnableTo(true);
        $this->updatePage->saveChanges();
    }

    /**
     * @When I edit its action so that it reduces price by ":discount%"
     */
    public function iEditItsActionSoThatItReducesPriceBy(string $discount): void
    {
        $this->formElement->specifyLastActionDiscount($discount);
    }

    /**
     * @When I add for variants scope without variants configured
     */
    public function iAddForVariantsScopeWithoutVariantsConfigured(): void
    {
        $this->formElement->addScope();
    }

    /**
     * @When I add percentage discount action without amount configured
     */
    public function iAddPercentageDiscountActionWithoutAmountConfigured(): void
    {
        $this->formElement->addAction();
    }

    /**
     * @When I add invalid percentage discount action with non number in amount
     */
    public function iAddInvalidPercentageDiscountActionWithNonNumberInAmount(): void
    {
        $this->formElement->addAction();
        $this->formElement->specifyLastActionDiscount('alot');
    }

    /**
     * @When /^I make (this catalog promotion) unavailable in the ("[^"]+" channel)$/
     * @When /^I make the ("[^"]+" catalog promotion) unavailable in the ("[^"]+" channel)$/
     */
    public function iMakeThisCatalogPromotionUnavailableInTheChannel(
        CatalogPromotionInterface $catalogPromotion,
        ChannelInterface $channel
    ): void {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);

        $this->formElement->uncheckChannel($channel->getName());

        $this->updatePage->saveChanges();
    }

    /**
     * @When /^I make (this catalog promotion) available in the ("[^"]+" channel)$/
     * @When /^I make ("[^"]+" catalog promotion) available in the ("[^"]+" channel)$/
     */
    public function iMakeThisCatalogPromotionAvailableInTheChannel(
        CatalogPromotionInterface $catalogPromotion,
        ChannelInterface $channel
    ): void {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);

        $this->formElement->checkChannel($channel->getName());

        $this->updatePage->saveChanges();
    }

    /**
     * @When /^I switch (this catalog promotion) availability from the ("[^"]+" channel) to the ("[^"]+" channel)$/
     * @When /^I switch ("[^"]+" catalog promotion) availability from the ("[^"]+" channel) to the ("[^"]+" channel)$/
     */
    public function iSwitchThisCatalogPromotionAvailabilityFromTheChannelToTheChannel(
        CatalogPromotionInterface $catalogPromotion,
        ChannelInterface $removedChannel,
        ChannelInterface $addedChannel
    ): void {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);

        $this->formElement->uncheckChannel($removedChannel->getName());
        $this->formElement->checkChannel($addedChannel->getName());

        $this->updatePage->saveChanges();
    }

    /**
     * @Then I should be notified that a discount amount should be between 0% and 100%
     */
    public function iShouldBeNotifiedThatADiscountAmountShouldBeBetween0And100Percent(): void
    {
        Assert::same($this->formElement->getValidationMessageForAction(), 'The percentage discount amount must be between 0% and 100%.');
    }

    /**
     * @Then I should be notified that a discount amount should be a number and cannot be empty
     */
    public function iShouldBeNotifiedThatADiscountAmountShouldBeANumber(): void
    {
        Assert::same($this->formElement->getValidationMessageForAction(), 'The percentage discount amount must be a number and can not be empty.');
    }

    /**
     * @Then there should be :amount catalog promotions on the list
     * @Then there should be :amount new catalog promotion on the list
     * @Then there should be an empty list of catalog promotions
     */
    public function thereShouldBeCatalogPromotionsOnTheList(int $amount = 0): void
    {
        $this->indexPage->open();

        Assert::same($this->indexPage->countItems(), $amount);
    }

    /**
     * @Then the catalog promotions named :firstName and :secondName should be in the registry
     */
    public function theCatalogPromotionsNamedShouldBeInTheRegistry(string ...$names): void
    {
        foreach ($names as $name) {
            Assert::true(
                $this->indexPage->isSingleResourceOnPage(['name' => $name]),
                sprintf('Cannot find catalog promotions with name "%s" in the list', $name)
            );
        }
    }

    /**
     * @Then it should have :code code and :name name
     */
    public function itShouldHaveCodeAndName(string $code, string $name): void
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['name' => $name, 'code' => $code]),
            sprintf('Cannot find catalog promotions with code "%s" and name "%s" in the list', $code, $name)
        );
    }

    /**
     * @Then /^("[^"]+" catalog promotion) should apply to ("[^"]+" variant) and ("[^"]+" variant)$/
     */
    public function itShouldHaveScope(CatalogPromotionInterface $catalogPromotion, ProductVariantInterface ...$variants): void
    {
        $this->updatePage->open(['id' => $catalogPromotion->getId()]);

        $selectedVariants = $this->formElement->getLastScopeVariantCodes();

        foreach ($variants as $productVariant) {
            Assert::inArray($productVariant->getCode(), $selectedVariants);
        }
    }

    /**
     * @Then /^it should apply to ("[^"]+" variant) and ("[^"]+" variant)$/
     * @Then /^this catalog promotion should be applied on ("[^"]+" variant)$/
     */
    public function itShouldAppyToVariants(ProductVariantInterface ...$variants): void
    {
        $selectedVariants = $this->formElement->getLastScopeVariantCodes();

        foreach ($variants as $productVariant) {
            Assert::inArray($productVariant->getCode(), $selectedVariants);
        }
    }

    /**
     * @Then /^this catalog promotion should not be applied on ("[^"]+" variant)$/
     */
    public function itShouldNotApplyToVariants(ProductVariantInterface ...$variants): void
    {
        $selectedVariants = $this->formElement->getLastScopeVariantCodes();

        foreach ($variants as $productVariant) {
            Assert::false(in_array($productVariant->getCode(), $selectedVariants));
        }
    }

    /**
     * @Then /^it should have "([^"]+)%" discount$/
     * @Then /^this catalog promotion should have "([^"]+)%" percentage discount$/
     */
    public function itShouldHaveDiscount(string $amount): void
    {
        Assert::same($this->formElement->getLastActionDiscount(), $amount);
    }

    /**
     * @Then there should still be only one catalog promotion with code :code
     */
    public function thereShouldStillBeOnlyOneCatalogPromotionWithCode(string $code): void
    {
        $this->indexPage->open();

        Assert::true($this->indexPage->isSingleResourceOnPage(['code' => $code]));
    }

    /**
     * @Then this catalog promotion should be usable
     */
    public function thisCatalogPromotionShouldBeUsable(): void
    {
        // Intentionally left blank
    }

    /**
     * @Then the catalog promotion :catalogPromotionName should be available in channel :channelName
     */
    public function theCatalogPromotionShouldBeAvailableInChannel(string $catalogPromotionName, string $channelName): void
    {
        Assert::true($this->indexPage->isSingleResourceOnPage(['name' => $catalogPromotionName, 'channels' => $channelName]));
    }

    /**
     * @Then /^(this catalog promotion) should be available in channel "([^"]+)"$/
     */
    public function thisCatalogPromotionShouldBeAvailableInChannel(
        CatalogPromotionInterface $catalogPromotion,
        string $channelName
    ): void {
        $this->indexPage->open();

        $this->theCatalogPromotionShouldBeAvailableInChannel($catalogPromotion->getName(), $channelName);
    }

    /**
     * @Then /^(this catalog promotion) should not be available in channel "([^"]+)"$/
     */
    public function thisCatalogPromotionShouldNotBeAvailableInChannel(
        CatalogPromotionInterface $catalogPromotion,
        string $channelName
    ): void {
        Assert::false(
            $this->indexPage->isSingleResourceOnPage(['name' => $catalogPromotion->getName(), 'channels' => $channelName])
        );
    }

    /**
     * @Then I should be notified that code and name are required
     */
    public function iShouldBeNotifiedThatCodeAndNameAreRequired(): void
    {
        Assert::same($this->createPage->getValidationMessage('code'), 'Please enter catalog promotion code.');
        Assert::same($this->createPage->getValidationMessage('name'), 'Please enter catalog promotion name.');
    }

    /**
     * @Then I should be notified that catalog promotion with this code already exists
     */
    public function iShouldBeNotifiedThatCatalogPromotionWithThisCodeAlreadyExists(): void
    {
        Assert::same($this->createPage->getValidationMessage('code'), 'The catalog promotion with given code already exists.');
    }

    /**
     * @Then /^(this catalog promotion) name should be "([^"]+)"$/
     */
    public function thisCatalogPromotionNameShouldBe(CatalogPromotionInterface $catalogPromotion, string $name): void
    {
        $this->iBrowseCatalogPromotions();

        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['code' => $catalogPromotion->getCode(), 'name' => $name,])
        );
    }

    /**
     * @Then /^(this catalog promotion) should be (labelled|described) as "([^"]+)" in ("[^"]+" locale)$/
     */
    public function thisCatalogPromotionLabelInLocaleShouldBe(
        CatalogPromotionInterface $catalogPromotion,
        string $field,
        string $value,
        string $localeCode
    ): void {
        $fieldsMapping = [
            'labelled' => 'label',
            'described' => 'description',
        ];

        Assert::same($this->formElement->getFieldValueInLocale($fieldsMapping[$field], $localeCode), $value);
    }

    /**
     * @Then I should not be able to edit its code
     */
    public function iShouldNotBeAbleToEditItsCode(): void
    {
        Assert::true($this->updatePage->isCodeDisabled());
    }

    /**
     * @Then I should be notified that at least 1 variant is required
     */
    public function iShouldBeNotifiedThatAtLeast1VariantIsRequired(): void
    {
        Assert::same($this->formElement->getValidationMessageForAction(), 'Please add at least 1 variant.');
    }
}
