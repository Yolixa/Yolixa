#![cfg(test)]

extern crate std;

use std::fmt::Debug;

use soroban_sdk::{
    events::Event as _,
    testutils::{
        Address as _, AuthorizedFunction, AuthorizedInvocation, Events as _, MockAuth,
        MockAuthInvoke,
    },
    token::{StellarAssetClient, TokenClient},
    vec, Address, Env, IntoVal, InvokeError, MuxedAddress, Symbol, Val, Vec,
};

use crate::{
    types::{
        FeeUpdatedEvent, PauseStatusChangedEvent, SplitTipEvent, TipEvent, TokenStatusChangedEvent,
        TreasuryUpdatedEvent,
    },
    Error, SplitPayout, SplitRecipient, YolixaTipRouter, YolixaTipRouterClient, MAX_FEE_BPS,
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

    fn asset_client(&self) -> StellarAssetClient<'_> {
        StellarAssetClient::new(&self.env, &self.token)
    }
}

fn expect_error<T: Debug>(result: Result<T, Result<Error, InvokeError>>, expected: Error) {
    match result {
        Err(Ok(actual)) => assert_eq!(actual, expected),
        other => panic!("expected contract error {expected:?}, got {other:?}"),
    }
}

fn expect_host_error<T: Debug>(result: Result<T, Result<Error, InvokeError>>) {
    assert!(
        matches!(result, Err(Err(_))),
        "expected host auth/invoke error, got {result:?}"
    );
}

fn expect_any_failure<T: Debug>(result: Result<T, Result<Error, InvokeError>>) {
    assert!(result.is_err(), "expected failure, got {result:?}");
}

fn setup(fee_bps: u32) -> Context {
    setup_with_sender_balance(fee_bps, 1_000_000_000)
}

fn setup_with_sender_balance(fee_bps: u32, sender_balance: i128) -> Context {
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
    if sender_balance > 0 {
        token_admin_client.mint(&sender, &sender_balance);
    }
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
            bps: 7_000,
        },
        SplitRecipient {
            recipient: second,
            bps: 3_000,
        },
    ]
}

fn recipients_3(env: &Env, first: Address, second: Address, third: Address) -> Vec<SplitRecipient> {
    vec![
        env,
        SplitRecipient {
            recipient: first,
            bps: 5_000,
        },
        SplitRecipient {
            recipient: second,
            bps: 3_000,
        },
        SplitRecipient {
            recipient: third,
            bps: 2_000,
        },
    ]
}

fn transfer_args(env: &Env, from: &Address, to: &Address, amount: i128) -> Vec<Val> {
    let to_muxed: MuxedAddress = to.clone().into();
    (from, to_muxed, amount).into_val(env)
}

fn tip_args(
    env: &Env,
    sender: &Address,
    creator: &Address,
    token: &Address,
    amount: i128,
    tip_id: u64,
) -> Vec<Val> {
    (sender, creator, token, amount, tip_id).into_val(env)
}

fn split_args(
    env: &Env,
    sender: &Address,
    recipients: &Vec<SplitRecipient>,
    token: &Address,
    amount: i128,
    tip_id: u64,
) -> Vec<Val> {
    (sender, recipients, token, amount, tip_id).into_val(env)
}

fn expected_auth(
    env: &Env,
    contract: Address,
    fn_name: &str,
    args: Vec<Val>,
    sub_invocations: std::vec::Vec<AuthorizedInvocation>,
) -> AuthorizedInvocation {
    AuthorizedInvocation {
        function: AuthorizedFunction::Contract((contract, Symbol::new(env, fn_name), args)),
        sub_invocations,
    }
}

fn transfer_auth(
    env: &Env,
    token: &Address,
    from: &Address,
    to: &Address,
    amount: i128,
) -> AuthorizedInvocation {
    AuthorizedInvocation {
        function: AuthorizedFunction::Contract((
            token.clone(),
            Symbol::new(env, "transfer"),
            transfer_args(env, from, to, amount),
        )),
        sub_invocations: std::vec![],
    }
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
        ctx.client().try_initialize(&ctx.admin, &ctx.treasury, &100),
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
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1),
        Error::Paused,
    );
}

#[test]
fn unpause_restores_tips() {
    let ctx = setup(100);

    ctx.client().pause(&ctx.admin);
    ctx.client().unpause(&ctx.admin);
    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 990);
}

#[test]
fn unsupported_token_fails() {
    let ctx = setup(100);
    let unsupported = Address::generate(&ctx.env);

    expect_error(
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &unsupported, &1_000_i128, &1),
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
fn same_sender_duplicate_tip_id_fails() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);

    expect_error(
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1),
        Error::DuplicateTip,
    );
}

#[test]
fn different_senders_can_reuse_same_numeric_tip_id() {
    let ctx = setup(100);
    let other_sender = Address::generate(&ctx.env);
    ctx.asset_client().mint(&other_sender, &1_000_000_i128);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &7);
    ctx.client()
        .tip(&other_sender, &ctx.creator_two, &ctx.token, &2_000_i128, &7);

    let first = ctx.client().get_tip(&ctx.sender, &7).unwrap();
    let second = ctx.client().get_tip(&other_sender, &7).unwrap();

    assert!(ctx.client().tip_exists(&ctx.sender, &7));
    assert!(ctx.client().tip_exists(&other_sender, &7));
    assert_eq!(first.sender, ctx.sender);
    assert_eq!(first.creator.unwrap(), ctx.creator);
    assert_eq!(first.gross_amount, 1_000);
    assert_eq!(second.sender, other_sender);
    assert_eq!(second.creator.unwrap(), ctx.creator_two);
    assert_eq!(second.gross_amount, 2_000);
}

#[test]
fn standard_tip_transfers_correct_amounts() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &10_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 9_900);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 100);
}

#[test]
fn fee_calculation_at_1_5_percent_works() {
    let ctx = setup(150);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &10_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 9_850);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 150);
}

#[test]
fn amount_where_fee_rounds_to_zero_works() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &99_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 99);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 0);
}

#[test]
fn zero_fee_configuration_works() {
    let ctx = setup(0);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 1_000);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 0);
}

#[test]
fn amount_near_overflow_boundary_returns_math_overflow() {
    let ctx = setup(MAX_FEE_BPS);
    let overflowing_amount = (i128::MAX / MAX_FEE_BPS as i128) + 1;

    expect_error(
        ctx.client().try_tip(
            &ctx.sender,
            &ctx.creator,
            &ctx.token,
            &overflowing_amount,
            &1,
        ),
        Error::MathOverflow,
    );
}

#[test]
fn creator_stats_update() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);
    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &2_000_i128, &2);

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
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1),
        Error::UnsupportedToken,
    );

    ctx.client().set_token(&ctx.admin, &ctx.token, &true);
    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &2);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 990);
}

#[test]
fn disabled_token_after_successful_use_fails() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);
    ctx.client().set_token(&ctx.admin, &ctx.token, &false);

    expect_error(
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &2),
        Error::UnsupportedToken,
    );
}

#[test]
fn changing_fee_affects_only_subsequent_tips() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &10_000_i128, &1);
    ctx.client().set_fee(&ctx.admin, &200);
    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &10_000_i128, &2);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 19_700);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 300);
}

#[test]
fn changing_treasury_routes_subsequent_fees_to_new_treasury() {
    let ctx = setup(100);
    let new_treasury = Address::generate(&ctx.env);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &10_000_i128, &1);
    ctx.client().set_treasury(&ctx.admin, &new_treasury);
    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &10_000_i128, &2);

    assert_eq!(ctx.token_client().balance(&ctx.treasury), 100);
    assert_eq!(ctx.token_client().balance(&new_treasury), 100);
}

#[test]
fn split_tip_with_2_creators_works() {
    let ctx = setup(100);
    let recipients = recipients_2(&ctx.env, ctx.creator.clone(), ctx.creator_two.clone());

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &10_000_i128, &1);

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

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &10_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 4_900);
    assert_eq!(ctx.token_client().balance(&ctx.creator_two), 2_940);
    assert_eq!(ctx.token_client().balance(&ctx.creator_three), 1_960);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 200);
}

#[test]
fn single_recipient_split_at_10000_bps_works() {
    let ctx = setup(100);
    let recipients = vec![
        &ctx.env,
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 10_000,
        },
    ];

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 990);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 10);
}

#[test]
fn max_split_recipients_exactly_succeeds() {
    let ctx = setup(100);
    let mut recipients = Vec::new(&ctx.env);
    let mut created = std::vec::Vec::new();

    for _ in 0..MAX_SPLIT_RECIPIENTS {
        let recipient = Address::generate(&ctx.env);
        created.push(recipient.clone());
        recipients.push_back(SplitRecipient {
            recipient,
            bps: 1_000,
        });
    }

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &10_000_i128, &1);

    for recipient in created {
        assert_eq!(ctx.token_client().balance(&recipient), 990);
    }
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 100);
}

#[test]
fn split_percentages_not_equal_to_10000_fail() {
    let ctx = setup(100);
    let recipients = vec![
        &ctx.env,
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 6_000,
        },
        SplitRecipient {
            recipient: ctx.creator_two.clone(),
            bps: 3_000,
        },
    ];

    expect_error(
        ctx.client()
            .try_tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1),
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
            bps: 5_000,
        },
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 5_000,
        },
    ];

    expect_error(
        ctx.client()
            .try_tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1),
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
            bps: 5_000,
        },
        SplitRecipient {
            recipient: ctx.creator.clone(),
            bps: 5_000,
        },
    ];

    expect_error(
        ctx.client()
            .try_tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1),
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
        ctx.client()
            .try_tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1),
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
            bps: 3_333,
        },
        SplitRecipient {
            recipient: ctx.creator_two.clone(),
            bps: 3_333,
        },
        SplitRecipient {
            recipient: ctx.creator_three.clone(),
            bps: 3_334,
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

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &10_000_i128, &1);

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
fn tip_receipts_track_sender_scoped_replay_state() {
    let ctx = setup(100);

    assert!(!ctx.client().tip_exists(&ctx.sender, &1));
    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);

    let receipt = ctx.client().get_tip(&ctx.sender, &1).unwrap();
    assert!(ctx.client().tip_exists(&ctx.sender, &1));
    assert_eq!(receipt.tip_id, 1);
    assert_eq!(receipt.sender, ctx.sender);
    assert_eq!(receipt.creator.unwrap(), ctx.creator);
    assert_eq!(receipt.gross_amount, 1_000);
    assert_eq!(receipt.creator_amount, 990);
    assert_eq!(receipt.platform_fee, 10);
    assert_eq!(receipt.recipient_count, 1);
}

#[test]
fn initialize_requires_admin_authorization() {
    let env = Env::default();
    let admin = Address::generate(&env);
    let treasury = Address::generate(&env);
    let contract_id = env.register(YolixaTipRouter, ());
    let client = YolixaTipRouterClient::new(&env, &contract_id);

    expect_host_error(client.try_initialize(&admin, &treasury, &100));

    let invoke = MockAuthInvoke {
        contract: &contract_id,
        fn_name: "initialize",
        args: (&admin, &treasury, 100_u32).into_val(&env),
        sub_invokes: &[],
    };
    client
        .mock_auths(&[MockAuth {
            address: &admin,
            invoke: &invoke,
        }])
        .initialize(&admin, &treasury, &100);

    assert_eq!(client.get_admin(), admin);
}

#[test]
fn set_fee_requires_stored_admin_authorization() {
    let ctx = setup(100);
    ctx.env.set_auths(&[]);

    expect_host_error(ctx.client().try_set_fee(&ctx.admin, &200));

    let invoke = MockAuthInvoke {
        contract: &ctx.contract_id,
        fn_name: "set_fee",
        args: (&ctx.admin, 200_u32).into_val(&ctx.env),
        sub_invokes: &[],
    };
    ctx.client()
        .mock_auths(&[MockAuth {
            address: &ctx.admin,
            invoke: &invoke,
        }])
        .set_fee(&ctx.admin, &200);

    assert_eq!(ctx.client().get_fee_bps(), 200);
}

#[test]
fn set_treasury_requires_stored_admin_authorization() {
    let ctx = setup(100);
    let new_treasury = Address::generate(&ctx.env);
    ctx.env.set_auths(&[]);

    expect_host_error(ctx.client().try_set_treasury(&ctx.admin, &new_treasury));

    let invoke = MockAuthInvoke {
        contract: &ctx.contract_id,
        fn_name: "set_treasury",
        args: (&ctx.admin, &new_treasury).into_val(&ctx.env),
        sub_invokes: &[],
    };
    ctx.client()
        .mock_auths(&[MockAuth {
            address: &ctx.admin,
            invoke: &invoke,
        }])
        .set_treasury(&ctx.admin, &new_treasury);

    assert_eq!(ctx.client().get_treasury(), new_treasury);
}

#[test]
fn set_token_requires_stored_admin_authorization() {
    let ctx = setup(100);
    ctx.env.set_auths(&[]);

    expect_host_error(ctx.client().try_set_token(&ctx.admin, &ctx.token, &false));

    let invoke = MockAuthInvoke {
        contract: &ctx.contract_id,
        fn_name: "set_token",
        args: (&ctx.admin, &ctx.token, false).into_val(&ctx.env),
        sub_invokes: &[],
    };
    ctx.client()
        .mock_auths(&[MockAuth {
            address: &ctx.admin,
            invoke: &invoke,
        }])
        .set_token(&ctx.admin, &ctx.token, &false);

    assert!(!ctx.client().is_token_enabled(&ctx.token));
}

#[test]
fn pause_and_unpause_require_stored_admin_authorization() {
    let ctx = setup(100);
    ctx.env.set_auths(&[]);

    expect_host_error(ctx.client().try_pause(&ctx.admin));

    let pause_invoke = MockAuthInvoke {
        contract: &ctx.contract_id,
        fn_name: "pause",
        args: (&ctx.admin,).into_val(&ctx.env),
        sub_invokes: &[],
    };
    ctx.client()
        .mock_auths(&[MockAuth {
            address: &ctx.admin,
            invoke: &pause_invoke,
        }])
        .pause(&ctx.admin);
    assert!(ctx.client().is_paused());

    ctx.env.set_auths(&[]);
    expect_host_error(ctx.client().try_unpause(&ctx.admin));

    let unpause_invoke = MockAuthInvoke {
        contract: &ctx.contract_id,
        fn_name: "unpause",
        args: (&ctx.admin,).into_val(&ctx.env),
        sub_invokes: &[],
    };
    ctx.client()
        .mock_auths(&[MockAuth {
            address: &ctx.admin,
            invoke: &unpause_invoke,
        }])
        .unpause(&ctx.admin);
    assert!(!ctx.client().is_paused());
}

#[test]
fn tip_requires_sender_authorization() {
    let ctx = setup(100);
    ctx.env.set_auths(&[]);

    expect_host_error(
        ctx.client()
            .try_tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1),
    );

    let creator_transfer = MockAuthInvoke {
        contract: &ctx.token,
        fn_name: "transfer",
        args: transfer_args(&ctx.env, &ctx.sender, &ctx.creator, 990),
        sub_invokes: &[],
    };
    let fee_transfer = MockAuthInvoke {
        contract: &ctx.token,
        fn_name: "transfer",
        args: transfer_args(&ctx.env, &ctx.sender, &ctx.treasury, 10),
        sub_invokes: &[],
    };
    let sub_invokes = [creator_transfer, fee_transfer];
    let root = MockAuthInvoke {
        contract: &ctx.contract_id,
        fn_name: "tip",
        args: tip_args(&ctx.env, &ctx.sender, &ctx.creator, &ctx.token, 1_000, 1),
        sub_invokes: &sub_invokes,
    };
    ctx.client()
        .mock_auths(&[MockAuth {
            address: &ctx.sender,
            invoke: &root,
        }])
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 990);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 10);
}

#[test]
fn tip_split_requires_sender_authorization() {
    let ctx = setup(100);
    let recipients = recipients_2(&ctx.env, ctx.creator.clone(), ctx.creator_two.clone());
    ctx.env.set_auths(&[]);

    expect_host_error(ctx.client().try_tip_split(
        &ctx.sender,
        &recipients,
        &ctx.token,
        &1_000_i128,
        &1,
    ));

    let first_transfer = MockAuthInvoke {
        contract: &ctx.token,
        fn_name: "transfer",
        args: transfer_args(&ctx.env, &ctx.sender, &ctx.creator, 693),
        sub_invokes: &[],
    };
    let second_transfer = MockAuthInvoke {
        contract: &ctx.token,
        fn_name: "transfer",
        args: transfer_args(&ctx.env, &ctx.sender, &ctx.creator_two, 297),
        sub_invokes: &[],
    };
    let fee_transfer = MockAuthInvoke {
        contract: &ctx.token,
        fn_name: "transfer",
        args: transfer_args(&ctx.env, &ctx.sender, &ctx.treasury, 10),
        sub_invokes: &[],
    };
    let sub_invokes = [first_transfer, second_transfer, fee_transfer];
    let root = MockAuthInvoke {
        contract: &ctx.contract_id,
        fn_name: "tip_split",
        args: split_args(&ctx.env, &ctx.sender, &recipients, &ctx.token, 1_000, 1),
        sub_invokes: &sub_invokes,
    };
    ctx.client()
        .mock_auths(&[MockAuth {
            address: &ctx.sender,
            invoke: &root,
        }])
        .tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1);

    assert_eq!(ctx.token_client().balance(&ctx.creator), 693);
    assert_eq!(ctx.token_client().balance(&ctx.creator_two), 297);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 10);
}

#[test]
fn tip_auth_tree_scopes_creator_and_treasury_transfers() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);

    assert_eq!(
        ctx.env.auths(),
        std::vec![(
            ctx.sender.clone(),
            expected_auth(
                &ctx.env,
                ctx.contract_id.clone(),
                "tip",
                tip_args(&ctx.env, &ctx.sender, &ctx.creator, &ctx.token, 1_000, 1),
                std::vec![
                    transfer_auth(&ctx.env, &ctx.token, &ctx.sender, &ctx.creator, 990),
                    transfer_auth(&ctx.env, &ctx.token, &ctx.sender, &ctx.treasury, 10),
                ],
            )
        )]
    );
}

#[test]
fn tip_split_auth_tree_scopes_all_recipient_and_treasury_transfers() {
    let ctx = setup(100);
    let recipients = recipients_2(&ctx.env, ctx.creator.clone(), ctx.creator_two.clone());

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1);

    assert_eq!(
        ctx.env.auths(),
        std::vec![(
            ctx.sender.clone(),
            expected_auth(
                &ctx.env,
                ctx.contract_id.clone(),
                "tip_split",
                split_args(&ctx.env, &ctx.sender, &recipients, &ctx.token, 1_000, 1),
                std::vec![
                    transfer_auth(&ctx.env, &ctx.token, &ctx.sender, &ctx.creator, 693),
                    transfer_auth(&ctx.env, &ctx.token, &ctx.sender, &ctx.creator_two, 297),
                    transfer_auth(&ctx.env, &ctx.token, &ctx.sender, &ctx.treasury, 10),
                ],
            )
        )]
    );
}

#[test]
fn tip_event_payload_is_emitted() {
    let ctx = setup(100);

    ctx.client()
        .tip(&ctx.sender, &ctx.creator, &ctx.token, &1_000_i128, &1);

    let expected = TipEvent {
        tip_id: 1,
        sender: ctx.sender,
        creator: ctx.creator,
        token: ctx.token,
        gross_amount: 1_000,
        creator_amount: 990,
        platform_fee: 10,
    }
    .to_xdr(&ctx.env, &ctx.contract_id);

    assert_eq!(
        ctx.env.events().all().filter_by_contract(&ctx.contract_id),
        std::vec![expected]
    );
}

#[test]
fn split_tip_event_payload_is_emitted() {
    let ctx = setup(100);
    let recipients = recipients_2(&ctx.env, ctx.creator.clone(), ctx.creator_two.clone());

    ctx.client()
        .tip_split(&ctx.sender, &recipients, &ctx.token, &1_000_i128, &1);

    let expected = SplitTipEvent {
        tip_id: 1,
        sender: ctx.sender,
        token: ctx.token,
        gross_amount: 1_000,
        creator_amount: 990,
        platform_fee: 10,
        payouts: vec![
            &ctx.env,
            SplitPayout {
                recipient: ctx.creator,
                bps: 7_000,
                gross_amount: 700,
                net_amount: 693,
            },
            SplitPayout {
                recipient: ctx.creator_two,
                bps: 3_000,
                gross_amount: 300,
                net_amount: 297,
            },
        ],
    }
    .to_xdr(&ctx.env, &ctx.contract_id);

    assert_eq!(
        ctx.env.events().all().filter_by_contract(&ctx.contract_id),
        std::vec![expected]
    );
}

#[test]
fn configuration_events_include_previous_and_new_values() {
    let ctx = setup(100);
    let new_treasury = Address::generate(&ctx.env);

    // Fee
    ctx.client().set_fee(&ctx.admin, &200);

    let expected_fee = FeeUpdatedEvent {
        admin: ctx.admin.clone(),
        previous_fee_bps: 100,
        new_fee_bps: 200,
    }
    .to_xdr(&ctx.env, &ctx.contract_id);

    assert_eq!(
        ctx.env.events().all().filter_by_contract(&ctx.contract_id),
        std::vec![expected_fee]
    );

    // Treasury
    ctx.client().set_treasury(&ctx.admin, &new_treasury);

    let expected_treasury = TreasuryUpdatedEvent {
        admin: ctx.admin.clone(),
        previous_treasury: ctx.treasury.clone(),
        new_treasury: new_treasury.clone(),
    }
    .to_xdr(&ctx.env, &ctx.contract_id);

    assert_eq!(
        ctx.env.events().all().filter_by_contract(&ctx.contract_id),
        std::vec![expected_treasury]
    );

    // Token
    ctx.client().set_token(&ctx.admin, &ctx.token, &false);

    let expected_token = TokenStatusChangedEvent {
        admin: ctx.admin.clone(),
        token: ctx.token.clone(),
        previous_enabled: true,
        enabled: false,
    }
    .to_xdr(&ctx.env, &ctx.contract_id);

    assert_eq!(
        ctx.env.events().all().filter_by_contract(&ctx.contract_id),
        std::vec![expected_token]
    );

    // Pause
    ctx.client().pause(&ctx.admin);

    let expected_pause = PauseStatusChangedEvent {
        admin: ctx.admin.clone(),
        previous_paused: false,
        paused: true,
    }
    .to_xdr(&ctx.env, &ctx.contract_id);

    assert_eq!(
        ctx.env.events().all().filter_by_contract(&ctx.contract_id),
        std::vec![expected_pause]
    );

    // Unpause
    ctx.client().unpause(&ctx.admin);

    let expected_unpause = PauseStatusChangedEvent {
        admin: ctx.admin.clone(),
        previous_paused: true,
        paused: false,
    }
    .to_xdr(&ctx.env, &ctx.contract_id);

    assert_eq!(
        ctx.env.events().all().filter_by_contract(&ctx.contract_id),
        std::vec![expected_unpause]
    );
}

#[test]
fn failed_creator_transfer_rolls_back_receipt_and_stats() {
    let ctx = setup_with_sender_balance(100, 989);

    expect_any_failure(ctx.client().try_tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    ));

    assert!(!ctx.client().tip_exists(&ctx.sender, &1));
    assert!(ctx.client().get_tip(&ctx.sender, &1).is_none());
    assert_eq!(ctx.token_client().balance(&ctx.creator), 0);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 0);

    let stats = ctx.client().get_creator_stats(&ctx.creator);
    assert_eq!(stats.tip_count, 0);
    assert_eq!(stats.gross_received, 0);
    assert_eq!(stats.net_received, 0);
}

#[test]
fn failed_treasury_fee_transfer_rolls_back_creator_payment_and_state() {
    let ctx = setup_with_sender_balance(100, 990);

    expect_any_failure(ctx.client().try_tip(
        &ctx.sender,
        &ctx.creator,
        &ctx.token,
        &1_000_i128,
        &1,
    ));

    assert!(!ctx.client().tip_exists(&ctx.sender, &1));
    assert!(ctx.client().get_tip(&ctx.sender, &1).is_none());
    assert_eq!(ctx.token_client().balance(&ctx.sender), 990);
    assert_eq!(ctx.token_client().balance(&ctx.creator), 0);
    assert_eq!(ctx.token_client().balance(&ctx.treasury), 0);

    let stats = ctx.client().get_creator_stats(&ctx.creator);
    assert_eq!(stats.tip_count, 0);
    assert_eq!(stats.gross_received, 0);
    assert_eq!(stats.net_received, 0);
}
