<?php

namespace PubSubHubbubSubscriber;

use Language;
use MediaWikiLangTestCase;
use ReflectionClass;

/**
 * @covers PubSubHubbubSubscriber\Subscription
 *
 * @group Database
 * @group PubSubHubbubSubscriber
 *
 * @licence GNU GPL v2+
 * @author Sebastian Brückner < sebastian.brueckner@student.hpi.uni-potsdam.de >
 */
class SubscriptionTest extends MediaWikiLangTestCase {

	protected function setUp() {
		parent::setUp();
		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'en' ),
			'wgLanguageCode' => 'en',
		) );
		$this->tablesUsed[] = 'push_subscriptions';

		$this->insertTestData( NULL, 'topic1', 'ThisSecretMustHaveExactly32Bytes', NULL, true, true );
		$this->insertTestData( NULL, 'topic2', 'ThisOneAlsoHasToHave32Characters', NULL, false, true );
	}

	public function insertTestData( $id, $topic, $secret, $expires, $confirmed, $unsubscribe ) {
		$subscription = new Subscription( $id, $topic, $secret, $expires, $confirmed, $unsubscribe );
		$subscription->update();
	}

	/**
	 * @dataProvider getInsertSubscriptionData
	 *
	 * @param mixed[] $insertData
	 * @param mixed[] $expectedData
	 */
	public function testSubscriptionInsert( $insertData, $expectedData ) {
		$reflection = new ReflectionClass( 'PubSubHubbubSubscriber\\Subscription' );
		$subscription = $reflection->newInstanceArgs( $insertData );
		$subscription->update();
		$this->assertSelect( 'push_subscriptions',
			array( 'psb_topic', 'psb_secret', 'psb_expires', 'psb_confirmed', 'psb_unsubscribe' ), '',
			$expectedData );
	}

	/**
	 * @dataProvider getUpdatedSubscriptionData
	 */
	public function testSubscriptionUpdate( $topic, $confirmed, $expectedData ) {
		$subscription = Subscription::findByTopic( $topic );
		$subscription->setConfirmed( $confirmed );
		$subscription->update();
		$this->assertSelect( 'push_subscriptions',
			array( 'psb_topic', 'psb_secret', 'psb_expires', 'psb_confirmed', 'psb_unsubscribe' ), '',
			$expectedData );
	}

	public function testGetAll() {
		$subscriptions = Subscription::getAll();
		$this->assertTrue( is_array( $subscriptions ) );
		// See setUp().
		$this->assertEquals( 2, count( $subscriptions ) );
		$this->assertEquals( 'topic1', $subscriptions[0]->getTopic() );
		$this->assertEquals( 'topic2', $subscriptions[1]->getTopic() );
	}

	public function testSubscriptionFindNothingByID() {
		$subscription = Subscription::findByID( 1000 );
		$this->assertNull( $subscription );
	}

	public function testSubscriptionFindNothingByTopic() {
		$subscription = Subscription::findByTopic( "topic1000" );
		$this->assertNull( $subscription );
	}

	public function testSubscriptionFindByID() {
		$subscription = new Subscription( NULL, 'topic3', 'EvenThisSecretNeeds32Characters!', NULL, true, false );
		$subscription->update();

		$subscription = Subscription::findByTopic( "topic3" );
		$subscription = Subscription::findByID( $subscription->getID() );
		$this->assertNotNull( $subscription );
		$this->assertEquals( "topic3", $subscription->getTopic() );
	}

	/**
	 * @dataProvider getDeletedSubscriptionData
	 */
	public function testSubscriptionDelete( $topic, $expectedData ) {
		$subscription = Subscription::findByTopic( $topic );
		$subscription->delete();
		$this->assertSelect( 'push_subscriptions', array( 'psb_topic' ), '', $expectedData );
	}

	public function getInsertSubscriptionData() {
		return array(
			array(
				array( NULL, 'topic3', 'EvenThisSecretNeeds32Characters!', NULL, true, true ),
				array(
					array( 'topic1', 'ThisSecretMustHaveExactly32Bytes', NULL, '1', '1' ),
					array( 'topic2', 'ThisOneAlsoHasToHave32Characters', NULL, '0', '1' ),
					array( 'topic3', 'EvenThisSecretNeeds32Characters!', NULL, '1', '1' ),
				),
			),
			array(
				array( NULL, 'topic4', 'EvenThatSecretNeeds32Characters!', NULL, false, true ),
				array(
					array( 'topic1', 'ThisSecretMustHaveExactly32Bytes', NULL, '1', '1' ),
					array( 'topic2', 'ThisOneAlsoHasToHave32Characters', NULL, '0', '1' ),
					array( 'topic4', 'EvenThatSecretNeeds32Characters!', NULL, '0', '1' ),
				),
			),
		);
	}

	public function getUpdatedSubscriptionData() {
		return array(
			array(
				"topic1",
				false,
				array(
					array( 'topic1', 'ThisSecretMustHaveExactly32Bytes', NULL, '0', '1' ),
					array( 'topic2', 'ThisOneAlsoHasToHave32Characters', NULL, '0', '1' ),
				),
			),
			array(
				"topic2",
				true,
				array(
					array( 'topic1', 'ThisSecretMustHaveExactly32Bytes', NULL, '1', '1' ),
					array( 'topic2', 'ThisOneAlsoHasToHave32Characters', NULL, '1', '1' ),
				),
			),
		);
	}

	public function getDeletedSubscriptionData() {
		return array(
			array(
				'topic1',
				array(
					array( 'topic2' ),
				),
			),
			array(
				'topic2',
				array(
					array( 'topic1' ),
				),
			),
		);
	}

}
