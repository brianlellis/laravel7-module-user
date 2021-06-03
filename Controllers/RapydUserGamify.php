<?php
use App\User;

class RapydUserGamify
{
  public static function gamifyGivePoint($user_id, $subject)
    {
        $user = User::findOrFail($user_id);

        if ($subject === 'article') {
            $user->givePoint(new GamifyPointArticle($user->id));
        } elseif ($subject === 'formupload') {
            $user->givePoint(new GamifyPointBondEdit($user->id));
        } elseif ($subject === 'bondedit') {
            $user->givePoint(new GamifyPointFormUpload($user->id));
        }

        Session::flash('gamify_action', 'Points Awarded');
        return back();
    }

    public static function gamifyUndoPoint($user_id, $subject)
    {
        $user = User::findOrFail($user_id);

        if ($subject === 'article') {
            $user->undoPoint(new GamifyPointArticle($user->id));
        } elseif ($subject === 'formupload') {
            $user->undoPoint(new GamifyPointBondEdit($user->id));
        } elseif ($subject === 'bondedit') {
            $user->undoPoint(new GamifyPointFormUpload($user->id));
        }

        Session::flash('gamify_action', 'Points Removed');
        return back();
    }

    public static function gamifyGetPoints($user_id)
    {
        $user = User::findOrFail($user_id);
        return $user->getPoints();
    }
}
