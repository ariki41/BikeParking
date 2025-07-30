<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Postalcode;

class AddressSearch extends Component
{
    public $postalcode;
    public $address1;

    protected $rules = [
        'postalcode' => 'required|regex:/^\d{3}-?\d{4}$/',
    ];

    protected $messages = [
        'postalcode.required' => '郵便番号は必須です。',
        'postalcode.regex' => '郵便番号の形式が正しくありません。例: 123-4567',
    ];

    public function searchAddress()
    {
        $this->validate();

        // ハイフンが含まれている場合は削除
        $normalizedPostalcode = str_replace('-', '', $this->postalcode);

        $addressSql = Postalcode::getAddress($normalizedPostalcode)->first();

        if ($addressSql) {
            $this->address1 = $addressSql->prefecture . $addressSql->city . $addressSql->town;
        } else {
            $this->addError('postalcode', '郵便番号に対応する住所が見つかりません。');
            $this->address1 = '';
        }
    }

    public function render()
    {
        return view('livewire.address-search');
    }
}
