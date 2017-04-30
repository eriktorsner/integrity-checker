<h2>Privacy and terms</h2>

<h3>What is this?</h3>

<p>
    The Integrity Checker plugin is a free WordPress plugin, it uses a backend API located at our servers to provide information about plugins and themes. The plugin and the backend service is owned and developed by Torgesta Technology AB, a Swedish company. The plugin itself is licenced under the GPL v2 software license. The backend service needed for the plugin to work is not open source and using the backend service comes with some restrictions.
</p><p>
    We have spent a great deal of time to develop the API itself as well as collecting all the data it serves. We're aiming to find a balance between providing a commercial service to large and small companies that benefits from the data we provide and at the same time providing a useful and free service for personal projects, non-profit organizations and the occasional "help I've been hacked" situation. While trying to find that balance, we are going to do test various ways to get as many happy users as possible while earning enough money to keep the service up and running. In the unfortunate situation that we can't find that balance, we'll almost certainly prioritize keeping the service up and running while perhaps sacrificing the happiness of some free users.
</p>


<h3>Anonymous users</h3>
<p>
    As an antonymous user, your access to the backend API is restricted in three ways: (1) You can do manual but not scheduled scans, (2) the file diff functionality is limited to a fixed number of requests and (3) the API will not provide you with alternate hashes. The anonymous API keys are limited to one site only.
</p>
<p>
    We figure this is enough to make a scan of all themes and plugins on a small to medium WordPress site and to examine any issues found a little bit further.
</p><p>
    The first time you use Integrity Checker, an API key will be automatically created and stored in your WordPress installation. We don't collect any personal information about you at this stage, but we do register the IP address that your requests comes from as well as any other information that's normally passed on in a http request header. No personal data, but data that could identify your WordPress installation. This is to give us some way of determining if an anonymous user is trying to bypass the limitations we've set. For anonymous usage, we reserve the right to block an API key, an IP number or even an entire API range at any time. We'd only do that to protect our servers against various kinds of abuse and please note that we decide what constitutes abuse of our systems.
</p>

<h3>Registered user</h3>
<p>
    If you're willing to register your email address with us, you will get some additional functionality. We get the benefit of knowing a little bit more about you and in return we provide a better service. The additional functionality is: (1) Monthly scheduled scans, (2) access to alternate hashes via the API and (3) a raised limit on the number of file diff requests you can make. The registered API keys are also limited to one site only.
</p><p>
    We are storing your email address and intend to use it for a few different reasons, the primary reason is that we want to be able to reach out to you. We take your privacy very seriously and we will <strong>never</strong> share your email address with any 3rd party organizations without your consent.
</p><p>
    For the most part, we will reach out to you with technical information about new features, tips and tricks or to share success stories from other users. We might also send you commercial offers about upgrading to on of our paid subscriptions or to inform you about offers from partners within the WordPress space. Our promise to you is that we will only ever send out information that we honestly believe you will benefit from and that it related to WordPress technical stuff like security, management, deployments etc. If you are no longer interested to receive emails from us, you can opt out from our email lists, but in that case we reserve the right to block the API key that is associated with your email address.
</p><p>
    For registered users, we also reserve the right to block an API key, an IP number or even an entire API range at any time. As with anonymous users, we'd only do that to protect our servers against various kinds of abuse and please note that we decide what constitutes abuse of our systems.
</p>

<h3>Paid users</h3>
<p>
    We offer various paid yearly subscriptions starting at $39 USD for one site. Paid subscriptions for Integrity Checker are only restricted in terms of the number of sites. Free and unlimited scheduling, file diff requests and naturally access to alternate hashes. The terms of API usage for these commercial offerings are defined for each different package.
</p>
<p>
    Please visit <a href="https://www.wpessentials.io/product/integrity-checker-subscription/" target="_blank">https://www.wpessentials.io/product/integrity-checker-subscription/</a> for more information.
</p>
<br><br><br>

<h3>Features explained</h3>
<h4>Scheduled scans</h4>
Scheduled scans is the ability to schedule scans to run on a regular schedule. It is available for registered and paying users. Registered users can schedule one scan per month while paid users can schedule unlimited amount of scans.

<h4>Diff</h4>
When Integrity Checker discovers a file that is modified when compared to it's original you can click the <i>Diff</i> link to get a visual comparison between the two files with the changes highlighted. Each file diff operation requires that the original file is requested via our API.

<h4>Alternate hashes</h4>
Sometimes, a plugin author can make modifications to individual files in a plugin without increasing the plugin version. The most common case is that the plugin readme.txt file gets updated, but happens a lot for other files as well. When this happens Integrity Checker will flag this file as modified but there's no real way to determine that it was modified on the plugin repository rather than in your WordPress installation. Alternate hashes fixes this by supplying multiple signatuers (hashes) for each file that has been modified in the repository. This functionality greatly reduces the number of false positives that can otherwise occur.




