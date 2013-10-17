<?php

namespace SMW;

/**
 * Observer for independent update transactions
 *
 * Using this observer can help to enforce loose coupling by having
 * a Publisher (ObservableSubject) sent a notification (state change)
 * to this observer which will independently act from the source of
 * the notification
 *
 * @note When testing round-trips, use the MockUpdateObserver instead
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateObserver extends Observer implements ContextAware, ContextInjector {

	/** @var ContextResource */
	protected $context = null;

	/**
	 * @since 1.9
	 *
	 * @param ContextResource
	 */
	public function invokeContext( ContextResource $context ) {
		$this->context = $context;
	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {

		// This is not as clean as it should be but to avoid to make
		// multiple changes at once we determine a default builder here
		// which at some point should vanish after pending changes have
		// been merged

		// LinksUpdateConstructed is injecting the builder via the setter
		// UpdateJob does not
		// ParserAfterTidy does not

		if ( $this->context === null ) {
			$this->context = new BaseContext();
		}

		return $this->context;
	}

	/**
	 * Store updater
	 *
	 * @note Is called from UpdateJob::run, LinksUpdateConstructed::process, and
	 * ParserAfterTidy::process
	 *
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 *
	 * @return true
	 */
	public function runStoreUpdater( ParserData $parserData ) {

		$updater = new StoreUpdater( $parserData->getData(), $this->withContext() );
		$updater->setUpdateStatus( $parserData->getUpdateStatus() )->doUpdate();

		return true;
	}

	/**
	 * UpdateJob dispatching
	 *
	 * Normally by the time the job is execute the store has been updated and
	 * data that belong to a property potentially are no longer are associate
	 * with a subject.
	 *
	 * [1] Immediate dispatching influences the performance during page saving
	 * since data that belongs to the property are loaded directly from the DB
	 * which has direct impact about the response time of the page in question.
	 *
	 * [2] Deferred dispatching uses a different approach to recognize necessary
	 * objects involved and deferres property/pageIds mapping to the JobQueue.
	 * This makes it unnecessary to load data from the DB therefore decrease
	 * performance degration during page update.
	 *
	 * @since 1.9
	 *
	 * @param TitleAccess $subject
	 */
	public function runUpdateDispatcher( TitleAccess $subject ) {

		$dispatcher = new UpdateDispatcherJob( $subject->getTitle() );
		$dispatcher->invokeContext( $this->withContext() );
		$dispatcher->run();

		return true;
	}

}