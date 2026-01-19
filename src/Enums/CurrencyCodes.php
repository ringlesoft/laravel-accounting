<?php

namespace RingleSoft\LaravelAccounting\Enums;

    /**
     * ISO 4217 Currency Codes Enum
     *
     * Contains all active and some historical currency codes
     * Values are the 3-letter ISO currency codes
     */
enum CurrencyCodes: string
{
    // Major Global Currencies
    case USD = 'USD'; // US Dollar
    case EUR = 'EUR'; // Euro
    case GBP = 'GBP'; // British Pound Sterling
    case JPY = 'JPY'; // Japanese Yen
    case CNY = 'CNY'; // Chinese Yuan
    case INR = 'INR'; // Indian Rupee
    case CAD = 'CAD'; // Canadian Dollar
    case AUD = 'AUD'; // Australian Dollar
    case CHF = 'CHF'; // Swiss Franc
    case HKD = 'HKD'; // Hong Kong Dollar

    // Europe
    case DKK = 'DKK'; // Danish Krone
    case SEK = 'SEK'; // Swedish Krona
    case NOK = 'NOK'; // Norwegian Krone
    case PLN = 'PLN'; // Polish Złoty
    case CZK = 'CZK'; // Czech Koruna
    case HUF = 'HUF'; // Hungarian Forint
    case RON = 'RON'; // Romanian Leu
    case BGN = 'BGN'; // Bulgarian Lev
    case HRK = 'HRK'; // Croatian Kuna
    case RSD = 'RSD'; // Serbian Dinar
    case BAM = 'BAM'; // Bosnia-Herzegovina Convertible Mark
    case ALL = 'ALL'; // Albanian Lek
    case MKD = 'MKD'; // Macedonian Denar
    case ISK = 'ISK'; // Icelandic Króna
    case UAH = 'UAH'; // Ukrainian Hryvnia
    case BYN = 'BYN'; // Belarusian Ruble
    case MDL = 'MDL'; // Moldovan Leu
    case GEL = 'GEL'; // Georgian Lari
    case AMD = 'AMD'; // Armenian Dram
    case AZN = 'AZN'; // Azerbaijani Manat
    case KZT = 'KZT'; // Kazakhstani Tenge
    case RUB = 'RUB'; // Russian Ruble

    // Americas
    case MXN = 'MXN'; // Mexican Peso
    case BRL = 'BRL'; // Brazilian Real
    case ARS = 'ARS'; // Argentine Peso
    case CLP = 'CLP'; // Chilean Peso
    case COP = 'COP'; // Colombian Peso
    case PEN = 'PEN'; // Peruvian Sol
    case VES = 'VES'; // Venezuelan Bolívar Soberano
    case UYU = 'UYU'; // Uruguayan Peso
    case PYG = 'PYG'; // Paraguayan Guaraní
    case BOB = 'BOB'; // Bolivian Boliviano
    case CRC = 'CRC'; // Costa Rican Colón
    case GTQ = 'GTQ'; // Guatemalan Quetzal
    case HNL = 'HNL'; // Honduran Lempira
    case NIO = 'NIO'; // Nicaraguan Córdoba
    case PAB = 'PAB'; // Panamanian Balboa
    case DOP = 'DOP'; // Dominican Peso
    case TTD = 'TTD'; // Trinidad and Tobago Dollar
    case JMD = 'JMD'; // Jamaican Dollar
    case BSD = 'BSD'; // Bahamian Dollar
    case BBD = 'BBD'; // Barbadian Dollar
    case XCD = 'XCD'; // East Caribbean Dollar
    case BZD = 'BZD'; // Belize Dollar

    // Asia
    case KRW = 'KRW'; // South Korean Won
    case IDR = 'IDR'; // Indonesian Rupiah
    case MYR = 'MYR'; // Malaysian Ringgit
    case SGD = 'SGD'; // Singapore Dollar
    case THB = 'THB'; // Thai Baht
    case VND = 'VND'; // Vietnamese Đồng
    case PHP = 'PHP'; // Philippine Peso
    case PKR = 'PKR'; // Pakistani Rupee
    case BDT = 'BDT'; // Bangladeshi Taka
    case LKR = 'LKR'; // Sri Lankan Rupee
    case NPR = 'NPR'; // Nepalese Rupee
    case MNT = 'MNT'; // Mongolian Tögrög
    case MMK = 'MMK'; // Myanmar Kyat
    case KHR = 'KHR'; // Cambodian Riel
    case LAK = 'LAK'; // Lao Kip
    case BND = 'BND'; // Brunei Dollar
    case MOP = 'MOP'; // Macanese Pataca
    case TWD = 'TWD'; // New Taiwan Dollar

    // Middle East
    case SAR = 'SAR'; // Saudi Riyal
    case AED = 'AED'; // UAE Dirham
    case QAR = 'QAR'; // Qatari Riyal
    case KWD = 'KWD'; // Kuwaiti Dinar
    case OMR = 'OMR'; // Omani Rial
    case BHD = 'BHD'; // Bahraini Dinar
    case JOD = 'JOD'; // Jordanian Dinar
    case ILS = 'ILS'; // Israeli New Shekel
    case LBP = 'LBP'; // Lebanese Pound
    case SYP = 'SYP'; // Syrian Pound
    case YER = 'YER'; // Yemeni Rial
    case IRR = 'IRR'; // Iranian Rial
    case IQD = 'IQD'; // Iraqi Dinar
    case AFN = 'AFN'; // Afghan Afghani

    // Africa
    case ZAR = 'ZAR'; // South African Rand
    case EGP = 'EGP'; // Egyptian Pound
    case NGN = 'NGN'; // Nigerian Naira
    case KES = 'KES'; // Kenyan Shilling
    case ETB = 'ETB'; // Ethiopian Birr
    case GHS = 'GHS'; // Ghanaian Cedi
    case UGX = 'UGX'; // Ugandan Shilling
    case TZS = 'TZS'; // Tanzanian Shilling
    case XAF = 'XAF'; // CFA Franc BEAC
    case XOF = 'XOF'; // CFA Franc BCEAO
    case MAD = 'MAD'; // Moroccan Dirham
    case DZD = 'DZD'; // Algerian Dinar
    case TND = 'TND'; // Tunisian Dinar
    case LYD = 'LYD'; // Libyan Dinar
    case SDG = 'SDG'; // Sudanese Pound
    case SOS = 'SOS'; // Somali Shilling
    case MUR = 'MUR'; // Mauritian Rupee
    case SCR = 'SCR'; // Seychellois Rupee
    case MWK = 'MWK'; // Malawian Kwacha
    case ZMW = 'ZMW'; // Zambian Kwacha
    case BWP = 'BWP'; // Botswana Pula
    case NAD = 'NAD'; // Namibian Dollar
    case MZN = 'MZN'; // Mozambican Metical
    case AOA = 'AOA'; // Angolan Kwanza
    case CDF = 'CDF'; // Congolese Franc
    case RWF = 'RWF'; // Rwandan Franc
    case BIF = 'BIF'; // Burundian Franc

    // Pacific/Oceania
    case NZD = 'NZD'; // New Zealand Dollar
    case FJD = 'FJD'; // Fijian Dollar
    case PGK = 'PGK'; // Papua New Guinean Kina
    case WST = 'WST'; // Samoan Tala
    case TOP = 'TOP'; // Tongan Paʻanga
    case VUV = 'VUV'; // Vanuatu Vatu
    case SBD = 'SBD'; // Solomon Islands Dollar

    // Special & Regional Currencies
    case XDR = 'XDR'; // Special Drawing Rights
    case XAU = 'XAU'; // Gold (Troy ounce)
    case XAG = 'XAG'; // Silver (Troy ounce)
    case XPT = 'XPT'; // Platinum (Troy ounce)
    case XPD = 'XPD'; // Palladium (Troy ounce)

    // Cryptocurrencies (not official ISO, but commonly used)
    case BTC = 'BTC'; // Bitcoin
    case ETH = 'ETH'; // Ethereum
    case LTC = 'LTC'; // Litecoin
    case XRP = 'XRP'; // Ripple

    // Historical/Obsolete (useful for legacy data)
    case VEB = 'VEB'; // Venezuelan Bolívar (old)
    case VEF = 'VEF'; // Venezuelan Bolívar Fuerte
    case BYR = 'BYR'; // Belarusian Ruble (old)
    case ROL = 'ROL'; // Romanian Leu (old)
    case TRL = 'TRL'; // Turkish Lira (old)
    case ZWL = 'ZWL'; // Zimbabwean Dollar
    case DEM = 'DEM'; // German Mark
    case FRF = 'FRF'; // French Franc
    case ITL = 'ITL'; // Italian Lira
    case ESP = 'ESP'; // Spanish Peseta

    /**
     * Get all active fiat currency codes
     * Excludes cryptocurrencies, precious metals, and special currencies
     */
    public static function fiatCurrencies(): array
    {
        $cryptos = ['BTC', 'ETH', 'LTC', 'XRP'];
        $specials = ['XDR', 'XAU', 'XAG', 'XPT', 'XPD'];

        return array_filter(
            self::cases(),
            static fn($case) => !in_array($case->value, array_merge($cryptos, $specials), true)
        );
    }

    /**
     * Get all major currencies (G10 + major emerging)
     */
    public static function majorCurrencies(): array
    {
        $major = ['USD', 'EUR', 'JPY', 'GBP', 'CHF', 'CAD', 'AUD', 'NZD', 'CNY', 'INR', 'BRL', 'RUB', 'MXN', 'ZAR', 'KRW'];

        return array_filter(
            self::cases(),
            fn($case) => in_array($case->value, $major)
        );
    }

    /**
     * Check if currency code is valid
     */
    public static function isValid(string $code): bool
    {
        return self::tryFrom($code) !== null;
    }

    /**
     * Get currency metadata
     */
    public function getMetadata(): array
    {
        return match($this) {
            // Major currencies with detailed info
            self::USD => ['name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'symbol_native' => '$', 'regions' => ['United States']],
            self::EUR => ['name' => 'Euro', 'symbol' => '€', 'decimal_digits' => 2, 'symbol_native' => '€', 'regions' => ['Eurozone']],
            self::GBP => ['name' => 'British Pound', 'symbol' => '£', 'decimal_digits' => 2, 'symbol_native' => '£', 'regions' => ['United Kingdom']],
            self::CNY => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_digits' => 2, 'symbol_native' => '元', 'regions' => ['China']],

            // Zero-decimal currencies
            self::VND => ['name' => 'Vietnamese Dong', 'symbol' => '₫', 'decimal_digits' => 0, 'symbol_native' => '₫', 'regions' => ['Vietnam']],
            self::KRW => ['name' => 'South Korean Won', 'symbol' => '₩', 'decimal_digits' => 0, 'symbol_native' => '₩', 'regions' => ['South Korea']],
            self::JPY => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_digits' => 0, 'symbol_native' => '￥', 'regions' => ['Japan']],

            // Three-decimal currencies
            self::BHD => ['name' => 'Bahraini Dinar', 'symbol' => '.د.ب', 'decimal_digits' => 3, 'symbol_native' => '.د.ب', 'regions' => ['Bahrain']],
            self::JOD => ['name' => 'Jordanian Dinar', 'symbol' => 'د.ا', 'decimal_digits' => 3, 'symbol_native' => 'د.ا', 'regions' => ['Jordan']],
            self::KWD => ['name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك', 'decimal_digits' => 3, 'symbol_native' => 'د.ك', 'regions' => ['Kuwait']],
            self::OMR => ['name' => 'Omani Rial', 'symbol' => 'ر.ع.', 'decimal_digits' => 3, 'symbol_native' => 'ر.ع.', 'regions' => ['Oman']],
            self::TND => ['name' => 'Tunisian Dinar', 'symbol' => 'د.ت', 'decimal_digits' => 3, 'symbol_native' => 'د.ت', 'regions' => ['Tunisia']],

            // Cryptocurrencies
            self::BTC => ['name' => 'Bitcoin', 'symbol' => '₿', 'decimal_digits' => 8, 'symbol_native' => '₿', 'is_crypto' => true],
            self::ETH => ['name' => 'Ethereum', 'symbol' => 'Ξ', 'decimal_digits' => 18, 'symbol_native' => 'Ξ', 'is_crypto' => true],

            // Default fallback
            default => [
                'name' => $this->getCurrencyName(),
                'symbol' => $this->value,
                'decimal_digits' => 2,
                'symbol_native' => $this->value,
                'regions' => [],
                'is_crypto' => in_array($this->value, ['BTC', 'ETH', 'LTC', 'XRP']),
            ]
        };
    }

    /**
     * Get the number of decimal digits for this currency
     */
    public function decimalDigits(): int
    {
        return $this->getMetadata()['decimal_digits'] ?? 2;
    }

    /**
     * Get currency symbol
     */
    public function symbol(): string
    {
        return $this->getMetadata()['symbol'] ?? $this->value;
    }

    /**
     * Get human-readable currency name
     */
    private function getCurrencyName(): string
    {
        return match($this) {
            // Add specific cases as needed
            default => str_replace('_', ' ', ucfirst(strtolower($this->name)))
        };
    }

    /**
     * Get all currency codes as array
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all currency codes as associative array [code => name]
     */
    public static function allWithNames(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->getMetadata()['name'] ?? $case->value;
        }
        return $result;
    }

    /**
     * Get currencies by region/country
     */
    public static function byRegion(string $region): array
    {
        $regions = [
            'EUROPE' => ['EUR', 'GBP', 'CHF', 'DKK', 'SEK', 'NOK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'RSD'],
            'NORTH_AMERICA' => ['USD', 'CAD', 'MXN'],
            'SOUTH_AMERICA' => ['BRL', 'ARS', 'CLP', 'COP', 'PEN', 'VES', 'UYU', 'PYG', 'BOB'],
            'ASIA' => ['JPY', 'CNY', 'INR', 'KRW', 'SGD', 'HKD', 'TWD', 'THB', 'MYR', 'IDR', 'VND', 'PHP', 'PKR'],
            'MIDDLE_EAST' => ['SAR', 'AED', 'QAR', 'KWD', 'OMR', 'BHD', 'JOD', 'ILS', 'LBP'],
            'AFRICA' => ['ZAR', 'EGP', 'NGN', 'KES', 'ETB', 'GHS', 'XAF', 'XOF', 'MAD', 'DZD'],
            'OCEANIA' => ['AUD', 'NZD', 'FJD', 'PGK'],
        ];

        $region = strtoupper($region);
        $codes = $regions[$region] ?? [];

        return array_filter(
            self::cases(),
            static fn($case) => in_array($case->value, $codes, true)
        );
    }
}
