<?php

namespace NiklasSchmitt\Saml2\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NiklasSchmitt\Saml2\Auth;
use NiklasSchmitt\Saml2\Events\SignedIn;
use OneLogin\Saml2\Error as OneLoginError;

/**
 * Class Saml2Controller
 *
 * php-saml ends its own redirects with exit() unless it is told to "stay". The actions of this controller
 * ask for the URL instead and return a redirect response, so the request runs through the middleware and the
 * session of Laravel is saved (a listener of SignedOut that logs the user out would be lost otherwise).
 *
 * @package NiklasSchmitt\Saml2\Http\Controllers
 */
class Saml2Controller extends Controller
{
    /**
     * Render the metadata.
     *
     * @param Auth $auth
     *
     * @return \Illuminate\Support\Facades\Response
     *
     * @throws OneLoginError
     */
    public function metadata(Auth $auth)
    {
        $metadata = $auth->getMetadata();

        return response($metadata, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * Fires "SignedIn" event if a valid user is found. A response that can not be processed at all (no
     * SAMLResponse, an assertion that can not be decrypted, ...) is handled like an invalid response.
     *
     * @param Auth $auth
     *
     * @return RedirectResponse
     */
    public function acs(Auth $auth)
    {
        try {
            $errors = $auth->acs();
            $error = empty($errors) ? null : $auth->getLastErrorReason();
        } catch (\Throwable $e) {
            $errors = ['invalid_response'];
            $error = $this->reportException($auth, $e);
        }

        if (!empty($errors)) {
            return $this->redirectWithErrors($auth, $errors, $error);
        }

        $user = $auth->getSaml2User();

        event(new SignedIn($user, $auth));

        $redirectUrl = $user->getIntendedUrl();

        if ($redirectUrl) {
            return redirect()->intended($redirectUrl);
        }

        return redirect()->intended($auth->getTenant()->relay_state_url ?: config('saml2.loginRoute'));
    }

    /**
     * Process the SAML Logout Response / Logout Request sent by the IdP.
     *
     * Fires 'saml2.logoutRequestReceived' event if its valid.
     *
     * This means the user logged out of the SSO infrastructure, you 'should' log him out locally too.
     * (Listen to SignedOut.) A logout request of the IdP is answered with a redirect to the IdP.
     *
     * @param Auth $auth
     *
     * @return RedirectResponse
     */
    public function sls(Auth $auth)
    {
        try {
            $errors = $auth->sls(config('saml2.retrieveParametersFromServer'));
            $error = empty($errors) ? null : $auth->getLastErrorReason();
        } catch (\Throwable $e) {
            $errors = ['invalid_logout_message'];
            $error = $this->reportException($auth, $e);
        }

        if (!empty($errors)) {
            return $this->redirectWithErrors($auth, $errors, $error);
        }

        if ($answer = $auth->getSlsRedirectUrl()) {
            return $this->redirectAway($answer);
        }

        return redirect()->intended(config('saml2.logoutRoute')); // may be set a configurable default
    }

    /**
     * Initiate a login request.
     *
     * @param Illuminate\Http\Request $request
     * @param Auth $auth
     *
     * @return RedirectResponse
     *
     * @throws OneLoginError
     */
    public function login(Request $request, Auth $auth)
    {
        $redirectUrl = $auth->getTenant()->relay_state_url ?: config('saml2.loginRoute');

        return $this->redirectAway($auth->login($request->query('returnTo', $redirectUrl), stay: true));
    }

    /**
     * Initiate a logout request.
     *
     * @param Illuminate\Http\Request $request
     * @param Auth $auth
     *
     * @return RedirectResponse
     *
     * @throws OneLoginError
     */
    public function logout(Request $request, Auth $auth)
    {
        return $this->redirectAway($auth->logout(
            $request->query('returnTo'),
            $request->query('nameId'),
            $request->query('sessionIndex'),
            stay: true
        ));
    }

    /**
     * Redirects to the IdP with the headers php-saml sends with its own redirect.
     */
    protected function redirectAway(string $url): RedirectResponse
    {
        return redirect()->away($url)->withHeaders([
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Logs and flashes the errors and redirects to the error route.
     */
    protected function redirectWithErrors(Auth $auth, array $errors, ?string $error): RedirectResponse
    {
        $uuid = $auth->getTenant()->uuid;

        logger()->error('saml2.error_detail', compact('uuid', 'error'));
        session()->flash('saml2.error_detail', [$error]);

        logger()->error('saml2.error', $errors);
        session()->flash('saml2.error', $errors);

        return redirect()->to(config('saml2.errorRoute'));
    }

    /**
     * Logs an exception of php-saml (also an Error: php-saml hands the message of the client to PHP functions that throw
     * a ValueError, e.g. for an empty one). The message is not shown to the user (it can contain details of the message).
     */
    protected function reportException(Auth $auth, \Throwable $e): string
    {
        logger()->error('saml2.exception', [
            'uuid' => $auth->getTenant()->uuid,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        return 'The SAML message could not be processed.';
    }
}
