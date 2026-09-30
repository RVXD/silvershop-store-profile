<?php

declare(strict_types=1);

namespace SilverShop\StoreProfile\Extension;

use SilverShop\AddressFormats\AddressFormatRegistry;
use SilverShop\AddressFormats\AddressFormatter;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\i18n\i18n;

/**
 * Applied to {@link \SilverStripe\SiteConfig\SiteConfig}. Holds the shop's central identity (name, legal name, VAT,
 * contact details, logo) and a single structured store address, shared by other modules (invoicing seller, shipping
 * labels sender, …). Address labels/formatting come from {@link \SilverShop\AddressFormats}.
 *
 * @property \SilverStripe\SiteConfig\SiteConfig $owner
 * @property string $ShopName
 * @property string $LegalName
 * @property string $VatNumber
 * @property string $ContactEmail
 * @property string $ContactPhone
 * @property string $StoreStreet
 * @property string $StoreAddressLine2
 * @property string $StorePostcode
 * @property string $StoreCity
 * @property string $StoreRegion
 * @property string $StoreCountryCode
 * @property int $LogoID
 * @method Image Logo()
 */
class StoreProfileExtension extends Extension
{
    private static array $db = [
        'ShopName' => 'Varchar(255)',
        'LegalName' => 'Varchar(255)',
        'VatNumber' => 'Varchar(50)',
        'ContactEmail' => 'Varchar(255)',
        'ContactPhone' => 'Varchar(50)',
        'StoreStreet' => 'Varchar(255)',
        'StoreAddressLine2' => 'Varchar(255)',
        'StorePostcode' => 'Varchar(20)',
        'StoreCity' => 'Varchar(255)',
        'StoreRegion' => 'Varchar(100)',
        'StoreCountryCode' => 'Varchar(2)',
    ];

    private static array $has_one = [
        'Logo' => Image::class,
    ];

    private static array $owns = [
        'Logo',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $format = AddressFormatRegistry::singleton()->formatFor($this->getOwner()->StoreCountryCode);

        $identity = [
            HeaderField::create('StoreIdentityHeader', _t(self::class . '.Identity', 'Shop identity')),
            TextField::create('ShopName', _t(self::class . '.ShopName', 'Shop name'))
                ->setDescription(_t(self::class . '.ShopNameDesc', 'Public name. Defaults to the site title when blank.')),
            TextField::create('LegalName', _t(self::class . '.LegalName', 'Legal / trading name')),
            TextField::create('VatNumber', _t(self::class . '.VatNumber', 'VAT / tax number')),
            EmailField::create('ContactEmail', _t(self::class . '.ContactEmail', 'Contact email')),
            TextField::create('ContactPhone', _t(self::class . '.ContactPhone', 'Contact phone')),
            $logo = UploadField::create('Logo', _t(self::class . '.Logo', 'Logo')),
        ];
        $logo->setFolderName('store-profile');
        $logo->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);

        $address = [
            HeaderField::create('StoreAddressHeader', _t(self::class . '.Address', 'Store / return address')),
            TextField::create('StoreStreet', $format->labelFor('Address', _t(self::class . '.Street', 'Street + number'))),
            TextField::create('StoreAddressLine2', _t(self::class . '.AddressLine2', 'Address line 2')),
            TextField::create('StorePostcode', $format->labelFor('PostalCode', _t(self::class . '.Postcode', 'Postcode'))),
            TextField::create('StoreCity', $format->labelFor('City', _t(self::class . '.City', 'City'))),
            TextField::create('StoreRegion', $format->labelFor('State', _t(self::class . '.Region', 'State / province'))),
            DropdownField::create('StoreCountryCode', _t(self::class . '.Country', 'Country'), $this->countryOptions())
                ->setEmptyString(_t(self::class . '.ChooseCountry', '(choose a country)')),
        ];

        // Hide the region field for countries that don't use one (e.g. NL/BE/DE/GB).
        if ($format->isHidden('State')) {
            unset($address[5]);
        }

        $tab = $fields->fieldByName('Root.Shop') ? 'Root.Shop.ShopTabs.StoreProfile' : 'Root.StoreProfile';
        $fields->addFieldsToTab($tab, array_merge($identity, $address));
    }

    /**
     * Public shop name, falling back to the legal name and then the site title.
     */
    public function StoreProfileName(): string
    {
        return (string) ($this->getOwner()->ShopName
            ?: $this->getOwner()->LegalName
            ?: $this->getOwner()->Title);
    }

    public function hasStoreAddress(): bool
    {
        return trim((string) $this->getOwner()->StoreStreet) !== ''
            && trim((string) $this->getOwner()->StoreCity) !== '';
    }

    /**
     * Address data in the generic shape the formatter/consumers expect.
     *
     * @return array<string, string>
     */
    public function StoreAddressData(): array
    {
        $code = strtolower((string) $this->getOwner()->StoreCountryCode);
        $countries = i18n::getData()->getCountries();
        $countryName = $code !== '' ? (string) ($countries[$code] ?? strtoupper($code)) : '';

        return [
            'Name' => $this->StoreProfileName(),
            'Company' => (string) $this->getOwner()->LegalName,
            'Address' => (string) $this->getOwner()->StoreStreet,
            'AddressLine2' => (string) $this->getOwner()->StoreAddressLine2,
            'City' => (string) $this->getOwner()->StoreCity,
            'State' => (string) $this->getOwner()->StoreRegion,
            'PostalCode' => (string) $this->getOwner()->StorePostcode,
            'Country' => $countryName,
        ];
    }

    public function StoreFormattedAddress(bool $html = false): string
    {
        return AddressFormatter::singleton()->format(
            $this->StoreAddressData(),
            (string) $this->getOwner()->StoreCountryCode,
            $html
        );
    }

    /**
     * @return array<string, string>
     */
    private function countryOptions(): array
    {
        $countries = i18n::getData()->getCountries();
        $options = [];
        foreach ($countries as $code => $name) {
            $options[strtoupper((string) $code)] = (string) $name;
        }
        asort($options);

        return $options;
    }
}
