#![cfg(test)]

extern crate std;

use std::fmt::Debug;

use soroban_sdk::{
    testutils::{Address as _, Events as _},
    token::{StellarAssetClient, TokenClient},
    vec, Address, Env, InvokeError, Vec,
};

use crate::{
    Error, SplitRecipient, YolixaTipRouter, YolixaTipRouterClient, MAX_FEE_BPS,
    MAX_SPLIT_RECIPIENTS,
};

struct Context {
    env: Env,
    contract_id: Address,
    admin: Address,
    treasury: Address,
    token: Address,
    sender: Address,
    creator: Address,
    creator_two: Address,
    creator_three: Address,
}

impl Context {
    fn client(&self) -> YolixaTipRouterClient<'_> {
        YolixaTipRouterClient::new(&self.env, &self.contract_id)
    }

    fn token_client(&self) -> TokenClient<'_> {
        TokenClient::new(&self.env, &self.token)
    }
}

fn expect_error<T: Debug>(result: Result<T, Result<Error, InvokeError>>, expected: Error) {
    assert_eq!(result, Err(Ok(expected)));
}

fn setup(fee_bps: u32) -> Context {
    let env = Env::default();
    env.mock_all_auths();

    let admin = Address::generate(&env);
    let treasury = Address::generate(&env);
    let token_admin = Address::generate(&env);
    let sender = Address::generate(&env);
    let creator = Address::generate(&env);
    let creator_two = Address::generate(&env);
    let creator_three = Address::generate(&env);

    let contract_id = env.register(YolixaTipRouter, ());
    let client = YolixaTipRouterClient::new(&env, &contract_id);
    client.initialize(&admin, &treasury, &fee_bps);

    let sac = env.register_stellar_asset_contract_v2(token_admin);
    let token = sac.address();
    let token_admin_client = StellarAssetClient::new(&env, &token);
    token_admin_client.mint(&sender, &1_000_000_000_i128);
    client.set_token(&admin, &token, &true);

    Context {
        env,
        contract_id,
        admin,
        treasury,
        token,
        sender,
        creator,
        creator_two,
        creator_three,
    }
}

fn recipients_2(env: &Env, first: Address, second: Address) -> Vec<SplitRecipient> {
    vec![
        env,
        SplitRecipient {
            recipient: first,
            bps: 7_000
        },
        SplitRecipient {
            recipient: second,
            bps: 3_000
        },
    ]
}

fn recipients_3(
    env: &Env,
    first: Address,
    second: Address,
    third: Address,
) -> Vec<SplitRecipient> {
    vec![
        env,
        SplitRecipient {
            recipient: first,
            bps: 5_000
        },
        SplitRecipient {
            recipient: second,
            bps: 3_000
        },
        SplitRecipient {
            recipient: third,
            bps: 2_000
        },
    ]
}

#[test]
fn initialize_succeeds() {
    let env = Env::default();
    env.mock_all_auths();
    let admin = Address::generate(&env);
    let treasury = Address::generate(&env);
    let contract_id = env.register(YolixaTipRouter, ());
    let client = YolixaTipRouterClient::new(&env, &contract_id);

    client.initialize(&admin, &treasury, &150);

    assert_eq!(client.get_admin(), admin);
    assert_eq!(client.get_treasury(), treasury);
    assert_eq!(client.get_fee_bps(), 150);
    assert!(!client.is_paused());
}

#[test]
fn initialize_twice_fails() {
    let ctx = setup(100);
    expect_error(
        ctx.client()
            .try_initialize(&ctx.admin, &ctx.treasury, &100),
        Error::AlreadyInitialized,
    );
}

#[test]
fn fee_above_max_fails() {
    let env = Env::default();
    env.mock_all_auths();
    let admin = Address::generate(&env);
    let treasury = Address::generate(&env);
    let contract_id = env.register(YolixaTipRouter, ());
    let client = YolixaTipRouterClient::new(&env, &contract_id);

    expect_error(
        client.try_initialize(&admin, &treasury, &(MAX_FEE_BPS + 1)),
        Error::InvalidFee,
    );
}

#[test]
fn unauthorized_admin_mutation_fails() {
    let ctx = setup(100);
    let wrong_admin = Address::generate(&ctx.env);

    expect_error(
        ctx.client().try_set_fee(&wrong_admin, &200),
        Error::Unauthorized,
    );
}

#[test]
fn set_fee_succeeds() {
    let ctx = setup(100);

    ctx.client().set_fee(&ctx.admin, &250);

    assert_eq!(ctx.client().get_fee_bps(), 250);
}

#[test]
fn treasury_change_succeeds() {
    let ctx = setup(100);
    let new_treasury = Address::generate(&ctx.env);

    ctx.client().set_treasury(&ctx.admin, &new_treasury);

    assert_eq!(ctx.client().get_treasury(), new_treasury);
}

#[test]
fn pause_blocks_tips() {
    let ctx = setup(100);

    ctx.client().pause(&ctx.admin);

    expect_error(
        ctx.client().try_tip(
            &ctx.sender,
            &ctx.creator,
            &ctx.token,
            &1_000_i128,
            &1,
        ),
        Error::Paused,
    );
}

#[test]
fn unpause_restores_tips() {
    let ctx = setup(100);

    ctx.client().pause(&ctx.admin);
    ctx.client().unpause(&ctx.admin);
    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    );

    assert_eq!(ctx.token_client().balance(&ctx.creator), 990);
}

#[test]
fn unsupported_token_fails() {
    let ctx = setup(100);
    let unsupported = Address::generate(&ctx.env);

    expect_error(
        ctx.client().try_tip(
            &ctx.sender,
            &ctx.creator,
            &unsupported,
            &1_000_i128,
            &1,
        ),
        Error::UnsupportedToken,
    );
}

#[test]
fn zero_amount_fails() {
    let ctx = setup(100);

    expect_error(
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &ctx.token, &0_i128, &1),
        Error::InvalidAmount,
    );
}

#[test]
fn negative_amount_fails() {
    let ctx = setup(100);

    expect_error(
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &ctx.token, &-1_i128, &1),
        Error::InvalidAmount,
    );
}

#[test]
fn self_tip_fails() {
    let ctx = setup(100);

    expect_error(
        ctx.client()
            .try_tip(&ctx.sender, &ctx.sender, &ctx.token, &1_000_i128, &1),
        Error::SelfTip,
    );
}

#[test]
fn duplicate_tip_id_fails() {
    let ctx = setup(100);

    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    );

    expect_error(
        ctx.client().try_tip(
            &ctx.sender,
            &ctx.creator,
            &ctx.token,
            &1_000_i128,
            &1,
        ),
        Error::DuplicateTip,
    );
}

#[test]
fn standard_tip_transfers_correct_amounts() {
    let ctx = setup(100);

    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &10_000_i128,
        &1,
    );

    assert_eq!(ctx.token_client().balance(&ctx.creator), 9_900);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 100);
}

#[test]
fn fee_calculation_at_1_5_percent_works() {
    let ctx = setup(150);

    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &10_000_i128,
        &1,
    );

    assert_eq!(ctx.token_client().balance(&ctx.creator), 9_850);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 150);
}

#[test]
fn zero_fee_configuration_works() {
    let ctx = setup(0);

    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    );

    assert_eq!(ctx.token_client().balance(&ctx.creator), 1_000);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 0);
}

#[test]
fn creator_stats_update() {
    let ctx = setup(100);

    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    );
    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &2_000_i128,
        &2,
    );

    let stats = ctx.client().get_creator_stats(&ctx.creator);
    assert_eq!(stats.tip_count, 2);
    assert_eq!(stats.gross_received, 3_000);
    assert_eq!(stats.net_received, 2_970);
}

#[test]
fn token_allowlisting_works() {
    let ctx = setup(100);

    assert!(ctx.client().is_token_enabled(&ctx.token));
    ctx.client().set_token(&ctx.admin, &ctx.token, &false);
    assert!(!ctx.client().is_token_enabled(&ctx.token));

    expect_error(
        ctx.client().try_tip(
            &ctx.sender,
            &ctx.creator,
            &ctx.token,
            &1_000_i128,
            &1,
        ),
        Error::UnsupportedToken,
    );

    ctx.client().set_token(&ctx.admin, &ctx.token, &true);
    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &2,
    );

    assert_eq!(ctx.token_client().balance(&ctx.creator), 990);
}

#[test]
fn split_tip_with_2_creators_works() {
    let ctx = setup(100);
    let recipients = recipients_2(&ctx.env, ctx.creator.clone(), ctx.creator_two.clone());

    ctx.client().tip_split(
        &ctx.sender,
        &recipients,
        &ctx.token,
        &10_000_i128,
        &1,
    );

    assert_eq!(ctx.token_client().balance(&ctx.creator), 6_930);
    assert_eq!(ctx.token_client().balance(&ctx.creator_two), 2_970);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 100);
}

#[test]
fn split_tip_with_3_creators_works() {
    let ctx = setup(200);
    let recipients = recipients_3(
        &ctx.env,
        ctx.creator.clone(),
        ctx.creator_two.clone(),
        ctx.creator_three.clone(),
    );

    ctx.client().tip_split(
        &ctx.sender,
        &recipients,
        &ctx.token,
        &10_000_i128,
        &1,
    );

    assert_eq!(ctx.token_client().balance(&ctx.creator), 4_900);
    assert_eq!(ctx.token_client().balance(&ctx.creator_two), 2_940);
    assert_eq!(ctx.token_client().balance(&ctx.creator_three), 1_960);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 200);
}

#[test]
fn split_percentages_not_equal_to_10000_fail() {
    let ctx = setup(100);
    let recipients = vec![
        &ctx.env,
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 6_000
        },
        SplitRecipient {
            recipient: ctx.creator_two.clone(),
            bps: 3_000
        },
    ];

    expect_error(
        ctx.client().try_tip_split(
            &ctx.sender,
            &recipients,
            &ctx.token,
            &1_000_i128,
            &1,
        ),
        Error::InvalidSplit,
    );
}

#[test]
fn duplicate_split_recipient_fails() {
    let ctx = setup(100);
    let recipients = vec![
        &ctx.env,
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 5_000
        },
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 5_000
        },
    ];

    expect_error(
        ctx.client().try_tip_split(
            &ctx.sender,
            &recipients,
            &ctx.token,
            &1_000_i128,
            &1,
        ),
        Error::DuplicateRecipient,
    );
}

#[test]
fn sender_included_in_recipients_fails() {
    let ctx = setup(100);
    let recipients = vec![
        &ctx.env,
        SplitRecipient {
            recipient: ctx.sender.clone(),
            bps: 5_000
        },
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 5_000
        },
    ];

    expect_error(
        ctx.client().try_tip_split(
            &ctx.sender,
            &recipients,
            &ctx.token,
            &1_000_i128,
            &1,
        ),
        Error::SelfTip,
    );
}

#[test]
fn too_many_recipients_fails() {
    let ctx = setup(100);
    let mut recipients = Vec::new(&ctx.env);
    let bps = 10_000 / (MAX_SPLIT_RECIPIENTS + 1);
    let mut allocated = 0_u32;

    for i in 0..(MAX_SPLIT_RECIPIENTS + 1) {
        let is_last = i == MAX_SPLIT_RECIPIENTS;
        let share = if is_last { 10_000 - allocated } else { bps };
        allocated += share;
        recipients.push_back(SplitRecipient {
            recipient: Address::generate(&ctx.env),
            bps: share,
        });
    }

    expect_error(
        ctx.client().try_tip_split(
            &ctx.sender,
            &recipients,
            &ctx.token,
            &1_000_i128,
            &1,
        ),
        Error::TooManyRecipients,
    );
}

#[test]
fn split_payout_totals_exactly_match_creator_amount() {
    let ctx = setup(100);
    let recipients = vec![
        &ctx.env,
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 3_333
        },
        SplitRecipient {
            recipient: ctx.creator_two.clone(),
            bps: 3_333
        },
        SplitRecipient {
            recipient: ctx.creator_three.clone(),
            bps: 3_334
        },
    ];

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &101_i128, &1);

    let paid = ctx.token_client().balance(&ctx.creator)
        + ctx.token_client().balance(&ctx.creator_two)
        + ctx.token_client().balance(&ctx.creator_three);
    assert_eq!(paid, 100);
    assert_eq!(ctx.token_client().balance(&ctx.creator), 34);
    assert_eq!(ctx.token_client().balance(&ctx.creator_two), 33);
    assert_eq!(ctx.token_client().balance(&ctx.creator_three), 33);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 1);
}

#[test]
fn split_stats_update_correctly() {
    let ctx = setup(100);
    let recipients = recipients_2(&ctx.env, ctx.creator.clone(), ctx.creator_two.clone());

    ctx.client().tip_split(
        &ctx.sender,
        &recipients,
        &ctx.token,
        &10_000_i128,
        &1,
    );

    let first = ctx.client().get_creator_stats(&ctx.creator);
    let second = ctx.client().get_creator_stats(&ctx.creator_two);

    assert_eq!(first.tip_count, 1);
    assert_eq!(first.gross_received, 7_000);
    assert_eq!(first.net_received, 6_930);
    assert_eq!(second.tip_count, 1);
    assert_eq!(second.gross_received, 3_000);
    assert_eq!(second.net_received, 2_970);
}

#[test]
fn tip_receipts_track_replay_state() {
    let ctx = setup(100);

    assert!(!ctx.client().tip_exists(&1));
    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    );

    let receipt = ctx.client().get_tip(&1).unwrap();
    assert!(ctx.client().tip_exists(&1));
    assert_eq!(receipt.tip_id, 1);
    assert_eq!(receipt.creator.unwrap(), ctx.creator);
    assert_eq!(receipt.gross_amount, 1_000);
    assert_eq!(receipt.creator_amount, 990);
    assert_eq!(receipt.platform_fee, 10);
    assert_eq!(receipt.recipient_count, 1);
}

#[test]
fn event_emission_can_be_verified() {
    let ctx = setup(100);

    ctx.client().tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    );

    let events = ctx.env.events().all().filter_by_contract(&ctx.contract_id);
    assert_eq!(events.events().len(), 1);
}
