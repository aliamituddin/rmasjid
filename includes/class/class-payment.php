<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 4/27/2019
 * Time: 5:16 PM
 *
 * @package Masjid/Transaction
 */

namespace Masjid\Transactions;

use Masjid\Helpers;
use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Payment' ) ) {

	/**
	 * Class Payment
	 */
	class Payment {
		/**
		 * Generate a new payment
		 *
		 * @param int $campaign_id campaign_id.
		 *
		 * @return bool|int
		 */
		public static function create_payment( $campaign_id ) {
			$result            = false;
			$find_from_session = self::find_payment_session( $campaign_id );
			if ( $find_from_session ) {
				$result = $find_from_session;
			} else {
				$title       = '#' . uniqid( 'ma', true );
				$new_payment = wp_insert_post(
					[
						'post_type'   => 'bayar',
						'post_title'  => strtoupper( $title ),
						'post_name'   => sanitize_title( $title ),
						'post_status' => 'publish',
					]
				);
				if ( $new_payment ) {
					self::save_payment_session( $campaign_id, $new_payment );
					Helpers\Helper::upfield(
						$new_payment,
						[
							'campaign_id' => $campaign_id,
							'status'      => 'waiting_payment',
						]
					);
					$result = $new_payment;
				}
			}

			return $result;
		}

		/**
		 * Continue a payment
		 *
		 * @param int    $payment_id  payment id.
		 * @param int    $campaign_id campaign id.
		 * @param int    $amount      donantion amount.
		 * @param string $name        person name.
		 * @param string $email       person email.
		 * @param int    $hide_name   either show or hide name as anonymous.
		 * @param string $message     message.
		 *
		 * @return array
		 */
		public static function continue_payment( $payment_id, $campaign_id, $amount, $name, $email, $hide_name, $message = '' ) {
			$result                 = [ 'status' => 'error' ];
			$datetime_now           = new DateTime();
			$datetime_now_timestamp = $datetime_now->getTimestamp();
			$availability           = self::is_campaign_available_to_continue_payment( $campaign_id );
			if ( 'success' === $availability['status'] ) {
				$unique          = str_pad( wp_rand( 0, pow( 10, 3 ) - 1 ), 3, '0', STR_PAD_LEFT );
				$total_amount    = (int) $amount + (int) $unique;
				$datetime_expiry = new DateTime();
				$datetime_expiry->modify( '+1 day' );
				$expiry_timestamp = $datetime_expiry->getTimestamp();
				Helpers\Helper::upfield(
					$payment_id,
					[
						'amount'           => $amount,
						'total_amount'     => $total_amount,
						'unique_amount'    => $unique,
						'name'             => $name,
						'email'            => $email,
						'hide_name'        => $hide_name,
						'message'          => $message,
						'expiry'           => $expiry_timestamp,
						'status'           => 'waiting_confirmation',
						'payment_datetime' => $datetime_now_timestamp,
					]
				);
				// Remove session current active payment.
				self::remove_payment_session( $campaign_id );
				$result['status'] = 'success';
			} else {
				$result = $availability;
			}

			return $result;
		}

		/**
		 * Confirm the payment
		 *
		 * @param int $payment_id payment id.
		 *
		 * @return array
		 */
		public static function confirm_payment( $payment_id ) {
			$result                 = [ 'status' => 'error' ];
			$datetime_now           = new DateTime();
			$datetime_now_timestamp = $datetime_now->getTimestamp();
			$status                 = Helpers\Helper::pfield( 'status', $payment_id );
			if ( 'waiting_confirmation' === $status ) {
				Helpers\Helper::upfield(
					$payment_id,
					[
						'status'                => 'waiting_validation',
						'confirmation_datetime' => $datetime_now_timestamp,
					]
				);
				$result['status'] = 'success';
			} else {
				$result['message'] = __( 'You are not allowed to perform this action', 'masjid' );
			}

			return $result;
		}

		/**
		 * Validate the payment
		 *
		 * @param int $payment_id payment id.
		 *
		 * @return array
		 */
		public static function validate_payment( $payment_id ) {
			$result                 = [ 'status' => 'error' ];
			$datetime_now           = new DateTime();
			$datetime_now_timestamp = $datetime_now->getTimestamp();
			$status                 = Helpers\Helper::pfield( 'status', $payment_id );
			if ( 'waiting_validation' === $status ) {
				$campaign_id = Helpers\Helper::pfield( 'campaign_id', $payment_id );
				// Make a charge since the payment is success.
				self::charge_payment_into_campaign( $campaign_id, $payment_id );
				// update some fields.
				Helpers\Helper::upfield(
					$payment_id,
					[
						'validated_by'        => wp_get_current_user()->ID,
						'status'              => 'done',
						'validation_datetime' => $datetime_now_timestamp,
					]
				);
				// TODO: Send email to notify user about their donation status.
				$result['status'] = 'success';
			} else {
				$result['message'] = __( 'You are not allowed to perform this action', 'masjid' );
			}

			return $result;
		}

		/**
		 * Reject the payment
		 *
		 * @param int $payment_id payment id.
		 *
		 * @return array
		 */
		public static function reject_payment( $payment_id ) {
			$result                 = [ 'status' => 'error' ];
			$datetime_now           = new DateTime();
			$datetime_now_timestamp = $datetime_now->getTimestamp();
			$status                 = Helpers\Helper::pfield( 'status', $payment_id );
			if ( 'waiting_validation' === $status ) {
				// update some fields.
				Helpers\Helper::upfield(
					$payment_id,
					[
						'rejected_by'        => wp_get_current_user()->ID,
						'status'             => 'rejected',
						'rejection_datetime' => $datetime_now_timestamp,
					]
				);
				// TODO: Send email to notify user about their donation status.
				$result['status'] = 'success';
			} else {
				$result['message'] = __( 'You are not allowed to perform this action', 'masjid' );
			}

			return $result;
		}

		/**
		 * Charge payment into campaign
		 *
		 * @param int $campaign_id .
		 * @param int $payment_id  .
		 */
		private static function charge_payment_into_campaign( $campaign_id, $payment_id ) {
			$datetime_now                   = new DateTime();
			$datetime_now_timestamp         = $datetime_now->getTimestamp();
			$campaign_target                = (int) Helpers\Helper::pfield( 'main_detail_target', $campaign_id );
			$campaign_collected             = (int) Helpers\Helper::pfield( 'main_detail_collected', $campaign_id );
			$payment_total_amount           = (int) Helpers\Helper::pfield( 'total_amount', $payment_id );
			$new_campaign_collected         = $campaign_collected + $payment_total_amount;
			$new_campaign_collected_percent = $new_campaign_collected * 100 / $campaign_target;
			Helpers\Helper::upfield(
				$campaign_id,
				[
					'main_detail_collected'         => $new_campaign_collected,
					'last_success_donation'         => $datetime_now_timestamp,
					'main_detail_collected_percent' => (int) $new_campaign_collected_percent,
				]
			);
		}

		/**
		 * Check campaign availability
		 *
		 * @param int $campaign_id .
		 *
		 * @return array
		 */
		public static function is_campaign_available_to_continue_payment( $campaign_id ) {
			$result    = [ 'status' => 'error' ];
			$target    = Helpers\Helper::pfield( 'main_detail_target', $campaign_id );
			$collected = Helpers\Helper::pfield( 'main_detail_collected', $campaign_id );
			$due_date  = Helpers\Helper::pfield( 'main_detail_due_date', $campaign_id );
			if ( $collected >= $target ) {
				$result['message'] = __( 'Campaign is closed due it already meet its target', 'masjid' );
			} else {
				if ( empty( $due_date ) ) {
					$result['status'] = 'success';
				} else {
					$datetime_now           = new DateTime();
					$datetime_now_timestamp = $datetime_now->getTimestamp();
					if ( $datetime_now_timestamp > $due_date ) {
						$result['message'] = __( 'Campaign is closed due it already meet its due date', 'masjid' );
					} else {
						$result['status'] = 'success';
					}
				}
			}

			return $result;
		}

		/**
		 * Save payment to session
		 *
		 * @param int $campaign_id    .
		 * @param int $new_payment_id .
		 */
		private static function save_payment_session( $campaign_id, $new_payment_id ) {
			$_SESSION[ 'pay_' . $campaign_id ] = $new_payment_id;
		}

		/**
		 * Find payment from session
		 *
		 * @param int $campaign_id .
		 *
		 * @return bool|string
		 */
		private static function find_payment_session( $campaign_id ) {
			return isset( $_SESSION[ 'pay_' . $campaign_id ] ) ? $_SESSION[ 'pay_' . $campaign_id ] : false;
		}

		/**
		 * Remove payment of session
		 *
		 * @param int $campaign_id .
		 */
		private static function remove_payment_session( $campaign_id ) {
			unset( $_SESSION[ 'pay_' . $campaign_id ] );
		}
	}
}