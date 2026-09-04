<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;

class ResetPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset passwords for test accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $emails = [
            'muhammad.farhan0659@student.unri.ac.id',
            'reno.widi0668@student.unri.ac.id',
            'zuchra.helwani@lecturer.unri.ac.id',
        ];

        foreach ($emails as $email) {
            $user = UserModel::where('email', $email)->first();
            if ($user) {
                $user->update(['password' => Hash::make('password123')]);
                $this->info("✅ Password reset for: {$user->nama_user}");
                $this->info("   Email: {$email}");
                $this->info("   Password: password123\n");
            }
        }

        $this->info("\n✅ All passwords have been reset!");
    }
}
