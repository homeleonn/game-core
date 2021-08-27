<?php

namespace App;

use DB;
use Core\Helpers\Common;

class ItemRepository
{
	private Application $app;
	private array $items;

	public function __construct(Application $app)
	{
		$this->app = $app;
		$this->items = Common::itemsOnKeys(
			DB::getAll('Select * from allitems'),
			['item_id']
		);
	}

	public function getItemById($itemId)
	{
		return $this->items[$itemId] ?? null;
	}
}