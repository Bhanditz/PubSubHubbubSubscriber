<?php

namespace PubSubHubbubSubscriber;

use ApiBase;
use ApiMain;

class SubscriptionCallback extends ApiBase {

	public function __construct( ApiMain $main, $name, $prefix = '' ) {
		parent::__construct( $main, $name, $prefix );

		#$manager = $this->getMain()->getModuleManager();
		#$manager->addModule( 'raw', 'format', 'ApiFormatRaw' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();

		$result->addValue( null, 'mime', "text/plain" );
		// TODO: Add actual condition here.
		if (true) {
			$result->addValue( null, 'text', $params['hub.challenge'] );
		} else {
			header( "Not Found", true, 404 );
			$result->addValue( null, 'text', "" );
		}
	}

	public function getAllowedParams() {
		return array(
			'hub.mode' => array(
				ApiBase::PARAM_TYPE => array( 'subscribe', 'unsubscribe' ),
				ApiBase::PARAM_REQUIRED => true,
			),
			'hub.topic' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'hub.challenge' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'hub.lease_seconds' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
		);
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'hub.mode' => 'The literal string "subscribe" or "unsubscribe", which matches the original request to th '
				. 'hub from the subscriber.',
			'hub.topic' => 'The topic URL given in the corresponding subscription request.',
			'hub.challenge' => 'A hub-generated, random string that MUST be echoed by the subscriber to verify the '
				. 'subscription.',
			'hub.lease_seconds' => 'The hub-determined number of seconds that the subscription will stay active before '
				. 'expiring, measured from the time the verification request was made from the hub to the subscriber. '
				. 'Hubs MUST supply this parameter for subscription requests. This parameter MAY be present for '
				. 'unsubscribe requests and MUST be ignored by subscribers during unsubscription.',
		) );
	}

	public function getDescription() {
		return array(
			'API module to handle requests from the PubSubHubbub hub.'
		);
	}

}
