<?php

namespace HD\App\Events\Handlers;

use HD\App\Events\AbstractEvent;
use HD\Core\DB;

defined( 'ABSPATH' ) || die;

final class PostViewsCleaner extends AbstractEvent {
	private const string DEFAULT_HOOK = '_clean_post_views_handler';

	protected string $interval = 'weekly';
	private string $table      = 'post_views';

	// -----------------------------------------

	public function __construct( ?string $hookName = null ) {
		parent::__construct(
			$hookName ?? self::DEFAULT_HOOK,
			$this->interval
		);
	}

	// -----------------------------------------

	public function handle(): void {
		if ( ! DB::tableExists( $this->table ) ) {
			return;
		}

		$sql = 'DELETE pv FROM ' . DB::backtickedTable( $this->table ) . ' AS pv
        	LEFT JOIN ' . DB::db()->posts . ' AS p ON pv.post_id = p.ID
        	WHERE p.ID IS NULL';

		$deleted = DB::db()->query( $sql );
		$deleted && $this->log( "Deleted {$deleted} rows from {$this->table}" );
	}
}
