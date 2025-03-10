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

namespace Sylius\Behat\Context\Api\Admin;

use ApiPlatform\Core\Api\IriConverterInterface;
use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Core\Model\CatalogPromotionInterface;
use Sylius\Component\Core\Model\CatalogPromotionScopeInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Promotion\Event\CatalogPromotionUpdated;
use Sylius\Component\Promotion\Model\CatalogPromotionActionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final class ManagingCatalogPromotionsContext implements Context
{
    private ApiClientInterface $client;

    private ResponseCheckerInterface $responseChecker;

    private MessageBusInterface $messageBus;

    private IriConverterInterface $iriConverter;

    private SharedStorageInterface $sharedStorage;

    public function __construct(
        ApiClientInterface $client,
        ResponseCheckerInterface $responseChecker,
        MessageBusInterface $messageBus,
        IriConverterInterface $iriConverter,
        SharedStorageInterface $sharedStorage
    ) {
        $this->client = $client;
        $this->responseChecker = $responseChecker;
        $this->messageBus = $messageBus;
        $this->iriConverter = $iriConverter;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When I create a new catalog promotion with :code code and :name name
     */
    public function iCreateANewCatalogPromotionWithCodeAndName(string $code, string $name): void
    {
        $this->client->buildCreateRequest();
        $this->client->addRequestData('code', $code);
        $this->client->addRequestData('name', $name);
        $this->client->create();
    }

    /**
     * @When I create a new catalog promotion without specifying its code and name
     */
    public function iCreateANewCatalogPromotionWithoutSpecifyingItsCodeAndName(): void
    {
        $this->client->buildCreateRequest();
        $this->client->create();
    }

    /**
     * @When I want to create a new catalog promotion
     */
    public function iWantToCreateNewCatalogPromotion(): void
    {
        $this->client->buildCreateRequest();
    }

    /**
     * @When I specify its :field as :value
     * @When I :field it :value
     */
    public function iSpecifyItsAs(string $field, string $value): void
    {
        $this->client->addRequestData($field, $value);
    }

    /**
     * @When I specify its :field as :value in :localeCode
     */
    public function iSpecifyItsAsIn(string $field, string $value, string $localeCode): void
    {
        $data = ['translations' => [$localeCode => ['locale' => $localeCode]]];
        $data['translations'][$localeCode][$field] = $value;

        $this->client->updateRequestData($data);
    }

    /**
     * @When I disable this catalog promotion
     */
    public function iDisableCatalogPromotion(): void
    {
        $this->client->updateRequestData(['enabled' => false]);
    }

    /**
     * @When I enable this catalog promotion
     */
    public function iEnableThisCatalogPromotion(): void
    {
        $this->client->updateRequestData(['enabled' => true]);
    }

    /**
     * @When I describe it as :description in :localeCode
     */
    public function iDescribeItAsIn(string $description, string $localeCode): void
    {
        $data = ['translations' => [$localeCode => ['locale' => $localeCode]]];
        $data['translations'][$localeCode]['description'] = $description;

        $this->client->updateRequestData($data);
    }

    /**
     * @When I make it available in channel :channel
     */
    public function iMakeItAvailableInChannel(ChannelInterface $channel): void
    {
        $this->client->addRequestData('channels', [$this->iriConverter->getIriFromItem($channel)]);
    }

    /**
     * @When /^I make (it) unavailable in (channel "[^"]+")$/
     */
    public function iMakeItUnavailableInChannel(CatalogPromotionInterface $catalogPromotion, ChannelInterface $channel): void
    {
        $channels = $this->responseChecker->getValue($this->client->show($catalogPromotion->getCode()), 'channels');

        foreach (array_keys($channels, $this->iriConverter->getIriFromItem($channel)) as $key) {
            unset($channels[$key]);
        }

        $this->client->addRequestData('channels', $channels);
    }

    /**
     * @When I (try to) add it
     */
    public function iAddIt(): void
    {
        $this->client->create();
    }

    /**
     * @When I rename the :catalogPromotion catalog promotion to :name
     */
    public function iRenameTheCatalogPromotionTo(CatalogPromotionInterface $catalogPromotion, string $name): void
    {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());
        $this->client->updateRequestData(['name' => $name]);
        $this->client->update();
    }

    /**
     * @When I want to modify a catalog promotion :catalogPromotion
     * @When I modify a catalog promotion :catalogPromotion
     */
    public function iWantToModifyACatalogPromotion(CatalogPromotionInterface $catalogPromotion): void
    {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());

        $this->sharedStorage->set('catalog_promotion', $catalogPromotion);
    }

    /**
     * @When /^I add action that gives ("[^"]+") percentage discount$/
     */
    public function iAddActionThatGivesPercentageDiscount(float $amount): void
    {
        $actions = [[
            'type' => CatalogPromotionActionInterface::TYPE_PERCENTAGE_DISCOUNT,
            'configuration' => [
                'amount' => $amount
            ],
        ]];

        $this->client->addRequestData('actions', $actions);
    }

    /**
     * @When /^I add another action that gives ("[^"]+") percentage discount$/
     */
    public function iAddAnotherActionThatGivesPercentageDiscount(float $amount): void
    {
        $actions = $this->client->getContent()['actions'];

        $additionalAction = [[
            'type' => CatalogPromotionActionInterface::TYPE_PERCENTAGE_DISCOUNT,
            'configuration' => [
                'amount' => $amount
            ],
        ]];

        $actions = array_merge_recursive($actions, $additionalAction);

        $this->client->addRequestData('actions', $actions);
    }

    /**
     * @When /^I add another scope that applies on ("[^"]+" variant)$/
     */
    public function iAddAnotherScopeThatGivesPercentageDiscount(ProductVariantInterface $productVariant): void
    {
        $scopes = $this->client->getContent()['scopes'];

        $additionalScope = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_VARIANTS,
            'configuration' => [
                'variants' => [$productVariant->getCode()],
            ],
        ]];

        $scopes = array_merge_recursive($scopes, $additionalScope);

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When /^I edit its action so that it reduces price by ("[^"]+")$/
     */
    public function iEditItsActionSoThatItReducesPriceBy(float $amount): void
    {
        $content = $this->client->getContent();
        $content['actions'][0]['configuration']['amount'] = $amount;
        $this->client->updateRequestData($content);
    }

    /**
     * @When I remove its every action
     */
    public function iRemoveItsEveryAction(): void
    {
        $content = $this->client->getContent();
        $content['actions'] = [];
        $this->client->setRequestData($content);
    }

    /**
     * @When I add invalid percentage discount action with non number in amount
     */
    public function iAddInvalidPercentageDiscountActionWithNonNumberInAmount(): void
    {
        $actions = [[
            'type' => CatalogPromotionActionInterface::TYPE_PERCENTAGE_DISCOUNT,
            'configuration' => [
                'amount' => 'text'
            ],
        ]];

        $this->client->addRequestData('actions', $actions);
    }

    /**
     * @When I save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->client->update();
    }

    /**
     * @When I browse catalog promotions
     */
    public function iBrowseCatalogPromotions(): void
    {
        $this->client->index();
    }

    /**
     * @When /^I add scope that applies on ("[^"]+" variant) and ("[^"]+" variant)$/
     * @When /^I add scope that applies on variants ("[^"]+" variant) and ("[^"]+" variant)$/
     */
    public function iAddScopeThatAppliesOnVariants(ProductVariantInterface $firstVariant, ProductVariantInterface $secondVariant): void
    {
        $scopes = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_VARIANTS,
            'configuration' => [
                'variants' => [
                    $firstVariant->getCode(),
                    $secondVariant->getCode(),
                ]
            ],
        ]];

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When I add catalog promotion scope for taxon without taxons
     */
    public function iAddCatalogPromotionScopeForTaxonWithoutTaxons(): void
    {
        $scopes = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_TAXONS,
            'configuration' => ['taxons' => []],
        ]];

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When I add catalog promotion scope for taxon with nonexistent taxons
     */
    public function iAddCatalogPromotionScopeForTaxonWithNonexistentTaxons(): void
    {
        $scopes = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_TAXONS,
            'configuration' => [
                'taxons' => [
                    'BAD_TAXON',
                    'EVEN_WORSE_TAXON',
                ]
            ],
        ]];

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When I add scope that applies on :taxon taxon
     */
    public function iAddScopeThatAppliesOnTaxon(TaxonInterface $taxon): void
    {
        $scopes = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_TAXONS,
            'configuration' => [
                'taxons' => [$taxon->getCode()]
            ],
        ]];

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When I remove its every scope
     */
    public function iRemoveItsEveryScope(): void
    {
        $content = $this->client->getContent();
        $content['scopes'] = [];
        $this->client->setRequestData($content);
    }

    /**
     * @When /^I edit ("[^"]+" catalog promotion) to be applied on ("[^"]+" variant)$/
     */
    public function iEditCatalogPromotionToBeAppliedOnVariant(
        CatalogPromotionInterface $catalogPromotion,
        ProductVariantInterface $productVariant
    ): void {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());
        $scopes = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_VARIANTS,
            'configuration' => [
                'variants' => [
                    $productVariant->getCode(),
                ]
            ],
        ]];

        $this->client->updateRequestData(['scopes' => $scopes]);
        $this->client->update();
    }

    /**
     * @When /^I edit ("[^"]+" catalog promotion) to be applied on ("[^"]+" taxon)$/
     */
    public function iEditCatalogPromotionToBeAppliedOnTaxon(
        CatalogPromotionInterface $catalogPromotion,
        TaxonInterface $taxon
    ): void {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());

        $content = $this->client->getContent();
        unset($content['scopes'][0]['configuration']['variants']);
        $content['scopes'][0]['type'] = CatalogPromotionScopeInterface::TYPE_FOR_TAXONS;
        $content['scopes'][0]['configuration']['taxons'] = [$taxon->getCode()];

        $this->client->setRequestData($content);;

        $this->client->update();
    }

    /**
     * @When /^I disable ("[^"]*" catalog promotion)$/
     */
    public function iDisableThisCatalogPromotion(CatalogPromotionInterface $catalogPromotion): void
    {
        $this->toggleCatalogPromotion($catalogPromotion, false);
    }

    /**
     * @When /^I enable ("[^"]*" catalog promotion)$/
     */
    public function iEnableCatalogPromotion(CatalogPromotionInterface $catalogPromotion): void
    {
        $this->toggleCatalogPromotion($catalogPromotion, true);
    }

    /**
     * @When /^I edit ("[^"]+" catalog promotion) to have ("[^"]+") discount$/
     */
    public function iEditCatalogPromotionToHaveDiscount(CatalogPromotionInterface $catalogPromotion, float $amount): void
    {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());
        $scopes = [[
            'type' => CatalogPromotionActionInterface::TYPE_PERCENTAGE_DISCOUNT,
            'configuration' => [
                'amount' => $amount,
            ],
        ]];

        $this->client->updateRequestData(['actions' => $scopes]);
        $this->client->update();
    }

    /**
     * @When I add catalog promotion scope with nonexistent type
     */
    public function iAddCatalogPromotionScopeWithNonexistentType(): void
    {
        $scopes = [[
            'type' => 'nonexistent_scope',
            'configuration' => [
                'config' => 'config'
            ],
        ]];

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When I add for variants scope with the wrong configuration
     */
    public function iAddForVariantsScopeWithTheWrongConfiguration(): void
    {
        $scopes = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_VARIANTS,
            'configuration' => [
                'variants' => ['wrong_code'],
            ],
        ]];

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When I add for variants scope without variants configured
     */
    public function iAddForVariantsScopeWithoutVariantsConfigured(): void
    {
        $scopes = [[
            'type' => CatalogPromotionScopeInterface::TYPE_FOR_VARIANTS,
            'configuration' => [
                'variants' => [],
            ],
        ]];

        $this->client->addRequestData('scopes', $scopes);
    }

    /**
     * @When I add percentage discount action without amount configured
     */
    public function iAddPercentageDiscountActionWithoutAmountConfigured(): void
    {
        $actions = [[
            'type' => CatalogPromotionActionInterface::TYPE_PERCENTAGE_DISCOUNT,
            'configuration' => [],
        ]];

        $this->client->addRequestData('actions', $actions);
    }

    /**
     * @When I add catalog promotion action with nonexistent type
     */
    public function iAddCatalogPromotionActionWithNonexistentType(): void
    {
        $actions = [[
            'type' => 'nonexistent_action',
            'configuration' => [],
        ]];

        $this->client->addRequestData('actions', $actions);
    }

    /**
     * @When /^I make (this catalog promotion) unavailable in the ("[^"]+" channel)$/
     * @When /^I make the ("[^"]+" catalog promotion) unavailable in the ("[^"]+" channel)$/
     */
    public function iMakeThisCatalogPromotionUnavailableInTheChannel(
        CatalogPromotionInterface $catalogPromotion,
        ChannelInterface $channel
    ): void {
        $catalogPromotionCode = $catalogPromotion->getCode();
        Assert::notNull($catalogPromotionCode);

        $this->client->buildUpdateRequest($catalogPromotionCode);
        $content = $this->client->getContent();
        foreach (array_keys($content['channels'], $this->iriConverter->getIriFromItem($channel)) as $key) {
            unset($content['channels'][$key]);
        }

        $this->client->setRequestData($content);
        $this->client->update();
    }

    /**
     * @When /^I make (this catalog promotion) available in the ("[^"]+" channel)$/
     * @When /^I make ("[^"]+" catalog promotion) available in the ("[^"]+" channel)$/
     */
    public function iMakeThisCatalogPromotionAvailableInTheChannel(
        CatalogPromotionInterface $catalogPromotion,
        ChannelInterface $channel
    ): void {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());
        $content = $this->client->getContent();
        $content['channels'][] = $this->iriConverter->getIriFromItem($channel);
        $this->client->updateRequestData(['channels' => $content['channels']]);
        $this->client->update();
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
        $catalogPromotionCode = $catalogPromotion->getCode();
        Assert::notNull($catalogPromotionCode);

        $this->client->buildUpdateRequest($catalogPromotionCode);
        $content = $this->client->getContent();
        foreach (array_keys($content['channels'], $this->iriConverter->getIriFromItem($removedChannel)) as $key) {
            unset($content['channels'][$key]);
        }

        $content['channels'][] = $this->iriConverter->getIriFromItem($addedChannel);
        $this->client->setRequestData($content);
        $this->client->update();
    }

    /**
     * @Then there should be :amount new catalog promotion on the list
     * @Then there should be :amount catalog promotions on the list
     * @Then there should be an empty list of catalog promotions
     */
    public function thereShouldBeNewCatalogPromotionOnTheList(int $amount = 0): void
    {
        Assert::count($this->responseChecker->getCollection($this->client->index()), $amount);
    }

    /**
     * @Then /^it should have ("[^"]+") discount$/
     */
    public function itShouldHaveDiscount(float $amount): void
    {
        $catalogPromotion = $this->responseChecker->getCollection($this->client->getLastResponse())[0];

        Assert::same($catalogPromotion['actions'][0]['configuration']['amount'], $amount);
    }

    /**
     * @Then /^this catalog promotion should have ("[^"]+") percentage discount$/
     */
    public function thisCatalogPromotionShouldHavePercentageDiscount(float $amount): void
    {
        $catalogPromotionActions = $this->responseChecker->getResponseContent($this->client->getLastResponse())['actions'];
        foreach ($catalogPromotionActions as $catalogPromotionAction) {
            if (
                $catalogPromotionAction['configuration']['amount'] === $amount &&
                $catalogPromotionAction['type'] === CatalogPromotionActionInterface::TYPE_PERCENTAGE_DISCOUNT
            ) {
                return;
            }
        }

        throw new \Exception(sprintf('There is no "%s" action with %f', CatalogPromotionActionInterface::TYPE_PERCENTAGE_DISCOUNT, $amount));
    }

    /**
     * @Then it should have :code code and :name name
     */
    public function itShouldHaveCodeAndName(string $code, string $name): void
    {
        Assert::true(
            $this->responseChecker->hasItemWithValues($this->client->index(), ['code' => $code, 'name' => $name]),
            sprintf('Cannot find catalog promotions with code "%s" and name "%s" in the list', $code, $name)
        );
    }

    /**
     * @Then the catalog promotions named :firstName and :secondName should be in the registry
     */
    public function theCatalogPromotionsNamedShouldBeInTheRegistry(string ...$names): void
    {
        foreach ($names as $name) {
            Assert::true(
                $this->responseChecker->hasItemWithValue($this->client->index(), 'name', $name),
                sprintf('Cannot find catalog promotions with name "%s" in the list', $name)
            );
        }
    }

    /**
     * @Then /^"[^"]+" catalog promotion should apply to ("[^"]+" variant) and ("[^"]+" variant)$/
     */
    public function catalogPromotionShouldApplyToVariants(ProductVariantInterface $firstVariant, ProductVariantInterface $secondVariant): void
    {
        Assert::same(
            ['variants' => [$firstVariant->getCode(), $secondVariant->getCode()]],
            $this->responseChecker->getCollection($this->client->getLastResponse())[0]['scopes'][0]['configuration']
        );
    }

    /**
     * @Then :catalogPromotionName catalog promotion should apply to all products from :taxon taxon
     */
    public function catalogPromotionShouldApplyToTaxon(string $catalogPromotionName, TaxonInterface $taxon): void
    {
        Assert::same(
            ['taxons' => [$taxon->getCode()]],
            $this->responseChecker->getCollection($this->client->getLastResponse())[0]['scopes'][0]['configuration']
        );
    }

    /**
     * @Then this catalog promotion should be usable
     */
    public function thisCatalogPromotionShouldBeUsable(): void
    {
        Assert::isInstanceOf($this->messageBus->getDispatchedMessages()[0]['message'], CatalogPromotionUpdated::class);
    }

    /**
     * @Then the catalog promotion :catalogPromotion should be available in channel :channel
     * @Then /^(this catalog promotion) should be available in (channel "[^"]+")$/
     */
    public function itShouldBeAvailableInChannel(CatalogPromotionInterface $catalogPromotion, ChannelInterface $channel): void
    {
        Assert::true(
            $this->responseChecker->hasValueInCollection(
                $this->client->show($catalogPromotion->getCode()),
                'channels',
                $this->iriConverter->getIriFromItem($channel)
            ),
            sprintf('Catalog promotion is not assigned to %s channel', $channel->getName())
        );
    }

    /**
     * @Then /^(this catalog promotion) should not be available in (channel "[^"]+")$/
     */
    public function itShouldNotBeAvailableInChannel(
        CatalogPromotionInterface $catalogPromotion,
        ChannelInterface $channel
    ): void {
        Assert::false(
            $this->responseChecker->hasValueInCollection(
                $this->client->show($catalogPromotion->getCode()),
                'channels',
                $this->iriConverter->getIriFromItem($channel)
            ),
            sprintf('Catalog promotion is assigned to %s channel', $channel->getName())
        );
    }

    /**
     * @Then I should be notified that it has been successfully edited
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEdited(): void
    {
        Assert::true(
            $this->responseChecker->isUpdateSuccessful($this->client->getLastResponse()),
            'Catalog promotion could not be edited'
        );
    }

    /**
     * @Then /^(this catalog promotion) name should be "([^"]+)"$/
     */
    public function thisCatalogPromotionNameShouldBe(CatalogPromotionInterface $catalogPromotion, string $name): void
    {
        $response = $this->client->show($catalogPromotion->getCode());

        Assert::true(
            $this->responseChecker->hasValue($response, 'name', $name),
            sprintf('Catalog promotion\'s name %s does not exist', $name)
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

        $response = $this->client->show($catalogPromotion->getCode());

        Assert::true($this->responseChecker->hasTranslation($response, $localeCode, $fieldsMapping[$field], $value));
    }

    /**
     * @Then /^(this catalog promotion) should be applied on ("[^"]+" variant)$/
     */
    public function thisCatalogPromotionShouldBeAppliedOnVariant(
        CatalogPromotionInterface $catalogPromotion,
        ProductVariantInterface $productVariant
    ): void {
        $this->client->show($catalogPromotion->getCode());

        Assert::true($this->catalogPromotionAppliesOnVariants($productVariant));
    }

    /**
     * @Then /^(this catalog promotion) should be applied on ("[^"]+" taxon)$/
     */
    public function thisCatalogPromotionShouldBeAppliedOnTaxon(
        CatalogPromotionInterface $catalogPromotion,
        TaxonInterface $taxon
    ): void {
        $this->client->show($catalogPromotion->getCode());

        Assert::true($this->catalogPromotionAppliesOnTaxons($taxon));
    }

    /**
     * @Then /^(this catalog promotion) should not be applied on ("[^"]+" variant)$/
     */
    public function thisCatalogPromotionShouldNotBeAppliedOn(
        CatalogPromotionInterface $catalogPromotion,
        ProductVariantInterface $productVariant
    ): void {
        $this->client->show($catalogPromotion->getCode());

        Assert::false($this->catalogPromotionAppliesOnVariants($productVariant));
    }

    /**
     * @Then I should be notified that code and name are required
     */
    public function iShouldBeNotifiedThatCodeAndNameAreRequired(): void
    {
        $validationError = $this->responseChecker->getError($this->client->getLastResponse());

        Assert::contains($validationError, 'code: Please enter catalog promotion code.');
        Assert::contains($validationError, 'name: Please enter catalog promotion name.');
    }

    /**
     * @Then I should be notified that catalog promotion with this code already exists
     */
    public function iShouldBeNotifiedThatCatalogPromotionWithThisCodeAlreadyExists(): void
    {
        $response = $this->client->getLastResponse();
        Assert::false(
            $this->responseChecker->isCreationSuccessful($response),
            'Catalog promotion has been created successfully, but it should not'
        );
        Assert::same(
            $this->responseChecker->getError($response),
            'code: The catalog promotion with given code already exists.'
        );
    }

    /**
     * @Then there should still be only one catalog promotion with code :code
     */
    public function thereShouldStillBeOnlyOneCatalogPromotionWithCode(string $code): void
    {
        Assert::count($this->responseChecker->getCollectionItemsWithValue($this->client->index(), 'code', $code), 1);
    }

    /**
     * @Then I should not be able to edit its code
     */
    public function iShouldNotBeAbleToEditItsCode(): void
    {
        $this->client->updateRequestData(['code' => 'NEW_CODE']);
        $this->client->update();

        Assert::false(
            $this->responseChecker->hasValue($this->client->getLastResponse(), 'code', 'NEW_CODE'),
            'The code has been changed, but it should not'
        );
    }

    /**
     * @Then I should be notified that a discount amount is required
     */
    public function iShouldBeNotifiedThatADiscountAmountIsRequired(): void
    {
        $response = $this->client->getLastResponse();
        Assert::false(
            $this->responseChecker->isCreationSuccessful($response),
            'Catalog promotion has been created successfully, but it should not'
        );
        Assert::contains(
            $this->responseChecker->getError($response),
            'The percentage discount amount must be configured.'
        );
    }

    /**
     * @Then /^I should be notified that type of (action|scope) is invalid$/
     */
    public function iShouldBeNotifiedThatTypeIsInvalid(string $field): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            sprintf('Catalog promotion %s type is invalid. Please choose a valid type.', $field)
        );
    }

    /**
     * @Then I should be notified that a discount amount should be between 0% and 100%
     */
    public function iShouldBeNotifiedThatADiscountAmountShouldBeBetween0And100(): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'The percentage discount amount must be between 0% and 100%.'
        );
    }

    /**
     * @Then I should be notified that a discount amount should be a number and cannot be empty
     */
    public function iShouldBeNotifiedThatDiscountAmountShouldBeNumber(): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'The percentage discount amount must be a number and can not be empty.'
        );
    }

    /**
     * @Then I should be notified that scope configuration is invalid
     */
    public function iShouldBeNotifiedThatScopeConfigurationIsInvalid(): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'Provided configuration contains errors. Please add only existing variants.'
        );
    }

    /**
     * @Then I should be notified that I must add at least one taxon
     */
    public function iShouldBeNotifiedThatIMustAddAtLeastOneTaxon(): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'Provided configuration contains errors. Please add at least 1 taxon.'
        );
    }

    /**
     * @Then I should be notified that I can add only existing taxon
     */
    public function iShouldBeNotifiedThatICanAddOnlyExistingTaxons(): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'Provided configuration contains errors. Please add only existing taxons.'
        );
    }

    /**
     * @Then I should be notified that at least 1 variant is required
     */
    public function iShouldBeNotifiedThatAtLeast1VariantIsRequired(): void
    {
        Assert::contains(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'Please add at least 1 variant.'
        );
    }

    private function catalogPromotionAppliesOnVariants(ProductVariantInterface ...$productVariants): bool
    {
        $response = $this->responseChecker->getResponseContent($this->client->getLastResponse());

        foreach ($productVariants as $productVariant) {
            if (!isset($response['scopes'][0]['configuration']['variants'])) {
                return false;
            }

            if ($this->hasVariantInConfiguration($response['scopes'][0]['configuration']['variants'], $productVariant) === false) {
                return false;
            }
        }

        return true;
    }

    private function catalogPromotionAppliesOnTaxons(TaxonInterface ...$taxons): bool
    {
        $response = $this->responseChecker->getResponseContent($this->client->getLastResponse());

        foreach ($taxons as $taxon) {
            if ($this->hasTaxonInConfiguration($response['scopes'][0]['configuration']['taxons'], $taxon) === false) {
                return false;
            }
        }

        return true;
    }

    private function hasVariantInConfiguration(array $configuration, ProductVariantInterface $productVariant): bool
    {
        foreach ($configuration as $productVariantIri) {
            if ($productVariantIri === $productVariant->getCode()) {
                return true;
            }
        }

        return false;
    }

    private function hasTaxonInConfiguration(array $configuration, TaxonInterface $taxon): bool
    {
        foreach ($configuration as $configuredTaxon) {
            if ($configuredTaxon === $taxon->getCode()) {
                return true;
            }
        }

        return false;
    }

    private function toggleCatalogPromotion(CatalogPromotionInterface $catalogPromotion, bool $enabled): void
    {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());

        $this->client->updateRequestData(['enabled' => $enabled]);
        $this->client->update();
    }
}
