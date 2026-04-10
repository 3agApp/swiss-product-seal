// Components
import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword() {
    return (
        <>
            <Head title="Forgot password" />

            <div className="space-y-6">
                <Form {...email.form()}>
                    {({ processing, errors, wasSuccessful }) => (
                        <>
                            {wasSuccessful && (
                                <div className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                                    <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                                    <div>
                                        <p className="font-medium">Check your email</p>
                                        <p className="mt-1 text-emerald-700 dark:text-emerald-300">
                                            We&apos;ve sent a password reset link to your email address. It may take a few minutes to arrive.
                                        </p>
                                    </div>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="email@example.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button
                                    className="w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    {wasSuccessful ? 'Resend reset link' : 'Email password reset link'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Or, return to</span>
                    <TextLink href={login()}>log in</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot password',
    description: 'Enter your email to receive a password reset link',
};
