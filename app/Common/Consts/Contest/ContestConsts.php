<?php

namespace App\Common\Consts\Contest;

class ContestConsts
{
    // create contest
    public static $CONTEST_SLATE_ID        = 'slate_id';
    public static $CONTEST_POSITION        = 'position';
    public static $CONTEST_ENTRY_FEE       = 'entry_fee';
    public static $CONTEST_MATCH_TYPE      = 'match_type';
    public static $CONTEST_TIER            = 'tier';
    public static $CONTEST_USER_PLAYER_ID  = 'user_player_id';
    public static $CONTEST_OPP_PLAYER_ID   = 'opp_player_id';
    public static $CONTEST_NUM_OF_ENTRIES  = 'num_of_entries';
    public static $CONTEST_PRIVATE         = 'private';

    // join contest
    public static $CONTEST_ID        = 'contest_id';
    public static $ENTRY_ID          = 'entry_id';
    public static $GROUP_ID          = 'group_id';
    public static $CONTEST_PLAYER_ID = 'player_id';

    // retrieve contests
    public static $CONTEST_STATUS    = 'status';

    public static function getPositions()
    {
        return array(PositionConsts::$POSITION_HC, PositionConsts::$POSITION_QB, PositionConsts::$POSITION_RB,
            PositionConsts::$POSITION_WR, PositionConsts::$POSITION_TE, PositionConsts::$POSITION_K,
            PositionConsts::$POSITION_DST);
    }

    public static function getTiers()
    {
        return array(TierConsts::$TIER_A, TierConsts::$TIER_B, TierConsts::$TIER_C, TierConsts::$TIER_D, TierConsts::$TIER_E);
    }

    public static function getEntryFees()
    {
        return array(EntryFeeConsts::$ENTRY_FEE_0, EntryFeeConsts::$ENTRY_FEE_1, EntryFeeConsts::$ENTRY_FEE_2, EntryFeeConsts::$ENTRY_FEE_5,
                     EntryFeeConsts::$ENTRY_FEE_10, EntryFeeConsts::$ENTRY_FEE_25, EntryFeeConsts::$ENTRY_FEE_50,
                     EntryFeeConsts::$ENTRY_FEE_100, EntryFeeConsts::$ENTRY_FEE_250, EntryFeeConsts::$ENTRY_FEE_500,
                     EntryFeeConsts::$ENTRY_FEE_1000, EntryFeeConsts::$ENTRY_FEE_10000);
    }

    public static function getMatchTypes()
    {
        return array(MatchTypeConsts::$MATCH_SET_OPPONENT, MatchTypeConsts::$MATCH_TYPE_ANY_CHALLENGER,
                     MatchTypeConsts::$MATCH_TYPE_TIER_RANKING);
    }

    public static function getContestStatues()
    {
        return array(ContestStatusConsts::$CONTEST_STATUS_PENDING, ContestStatusConsts::$CONTEST_STATUS_LIVE,
            ContestStatusConsts::$CONTEST_STATUS_FINISHED, ContestStatusConsts::$CONTEST_STATUS_HISTORY);
    }
}