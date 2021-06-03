<?php

namespace Rapyd\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use App\User;
use Rapyd\Model\UsergroupType;

class Usergroups extends Model
{
    use \Swis\Laravel\Fulltext\Indexable;

    protected $table = 'usergroups';
    protected $guarded = [];
    public $timestamps = false;

    protected $indexContentColumns = [];

    protected $indexTitleColumns = [
        'email',
        'name',
        'address_street',
        'address_city',
        'state.full',
        'address_zip',
    ];

    public function type() {
      return $this->hasOne(UsergroupType::class, 'id', 'usergroup_type_id');
    }

    // THIS IS REFERENCING A JOIN TABLE
    public function users() {
        return $this->belongsToMany(User::class, 'usergroup_users','usergroup_id','user_id');
    }

    public function phone_number()
    {
      if ($this->phone_main) {
        return "(" . substr($this->phone_main, 0, 3) . ") " . substr($this->phone_main, 3, 3) . "-" . substr($this->phone_main, 6);
      }

      return '';
    }

    public function address()
    {
      // Check for street 2 append if exists
      $street = $this->address_2 ? $this->address_street . ' ' . $this->address_2 : $this->address_street;
      if ($street) {
        return $street . ', ' .
          $this->address_city  . ', ' .
          $this->address_state . ' ' .
          $this->address_zip;
      } else {
        return '';
      }
    }

    public function get_coordinates()
    {
      if (!$this->address()) {
        return ['latitude' => '', 'longitude' => ''];
      }

      $address = urlencode($this->address());
      $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=' . config('google.key');
      $resp = Http::get($url)->json();

      // response status will be 'OK', if able to geocode given address
      if ($resp['status'] == 'OK') {
        // get the important data
        $lati = isset($resp['results'][0]['geometry']['location']['lat']) ? $resp['results'][0]['geometry']['location']['lat'] : "";
        $longi = isset($resp['results'][0]['geometry']['location']['lng']) ? $resp['results'][0]['geometry']['location']['lng'] : "";

        // verify if data is complete
        if ($lati && $longi) {
          $this->update(['address_latitude' => $lati, 'address_longitude' => $longi]);
          return ['latitude' => (float)$this->address_latitude, 'longitude' => (float)$this->address_longitude];
        }
      }
    }
}
