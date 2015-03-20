This is the demo site for my Bitcoin microtransaction plugin. For my Entrepreneurship in Engineering course, my team and I are building a wordpress plugin that will allow blog owners to charge small amounts of bitcoin for posts. My end goal for the semester is to be able to charge by the post, but afterwards I’d like to explore other business models, such as pay per minute.

Bitcoin interface based on the following repository
https://github.com/blockchain/receive_payment_php_demo

Here is the progression of MVPs I am hoping to acheive:
<ol>
<li>Separate out free and premium content. Completed 2/10/15. Using the blog-in-blog plugin, I have found a way to post free posts on a “Free” page and premium posts on “Premium” page. There might be a way to do this from within my own plugin, but for now this will be good enough. The premium content is still visible by anyone, but that was not the point of this iteration. I believe there’s a way to require other plugins in your install.</li>
<li>Create a paywall for entire page. This should be easier than a paywall by post. Currently in development. I will start with just a redirect page with a button that will take you to the premium page. Then from there I will integrate the bitcoin software, and you will be redirected once the account’s master wallet receives payment. The user will then have unlimited access to all premium content. I’m not sure how long access will be valid though. Ideally it will be saved in a session variable and be valid for 24 hours.</li>
<li>Create paywall by post. This is the fine-grained transaction process I am hoping for. Instead of paying to access the page, you pay to get access to each post. Hopefully this will be a lot of the same code from iteration 2.</li>
Security/ User Experience Enhancements.  Once step 3 is working, I will go back and ensure everything is secure enough for major financial transactions. I would also like to allow users to sign up and save their wallet information, to allow one click transactions. This would definitely entice users to purchase multiple articles in one sitting.</li>

</ol>
