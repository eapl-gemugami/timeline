# 🌀 tl - your own micro-blogging site

Forked by [eapl.me](https://eapl.mx) from [timeline](https://github.com/sorenpeter/timeline)

## 🧶 What is tl?

ActivityPub which is based on Twitter/X is a great micro-blogging platform, although it's implementations (GoToSocial, Mastodon) have an important problem for us, they are difficult and expensive to be self-hosted, and is almost impossible to use a shared hosting.

Our goal is to offer a minimalistic but complete way to host a micro-blogging timeline, which also allows to watch other users timelines, and reply to their messages.

`tl` is a PHP application on top of the a [texudus](https://texudus.readthedocs.io/) file specification to offer a whole micro-blogging experience.

If offers you a space to post texts (twts), images and links to your text feed, as well as following other feeds and engaging in conversations by posting replies.

`tl` also supports Web Mentions as a form of [Linkback](https://en.wikipedia.org/wiki/Linkback), to be notified of `@mentions` from feeds you are not currently following.

At the same time providing a good looking basic design with the help of [Simple.css](https://simplecss.org), which allows you to customize  the look and feel. Even to the level where timeline aligns with the design of your excsing webpage, like I did on: [darch.dk/timeline](https://darch.dk/timeline).

![](media/screenshot.png)
_Conversation view with replies / Profile view / Gallery View_

## 🚨 DISCLAIMER / WARNING

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

## 🛠 Installation and setup

0. You need to have a webhosting with **PHP 8** and perferable running Apache or similar for timeline to work.

	> There are free options, but I would suggest that you pay for your hosting and also get a nice domain, so you have more ownership over your data and online idetenty.

1. Download the code from https://github.com/sorenpeter/timeline as a zip

2. Upload the content of the zip to you webhosting using a FTP client

	- The default would be to put eveything from within the timeline-main folder in the root so you will have:

	```
	www.example.net/timeline/            (go here to see your timeline)
	www.example.net/timeline/gallery/    (go here to see your gallery)
	www.example.net/timeline/post/       (go here to post to your feed)
	www.example.net/twtxt.txt            (where you feed lives and other can follow you)
	www.example.net/avatar.png           (your pretty picture)
	```

	- or you can rename the folder `timeline` to something else

3. Go to the `private` folder and make a copy of `config_template.ini` and save it as `config.ini`

4. Open `config.ini` and edit the setting to you liking and setup

5. Open up `www.example.net/timeline/` in your browser and check for any errors

### Webfinger endpoint setup

6. For allowing others to look you on using webfinger, you need to move the `.well-known` folder from within the `_webfinger_endpoint` to the root of your domain, so it is accesable from www.example.net/.well-know/webfinger

7. You also need to edit the `index.php` file wihtin the `.well-know/webfinger` folder and set the correct path for you timeline installation in `$timeline_dir` variable.


## 🎨 Customization

- Upload your own `avatar.png` (can also be a .jpg or .gif)
	- Edit your `twtxt.txt` and `config.ini` with the correct path

- Copy `custom_template.css` to `custom.css` and try changinge the coloers to you liking


# TODO

## 🐞 Bugs to fix

## 🚀 Features to code

# 🙏 Credits / shoutouts

## Ideas and inspiration

- [twtxt](https://twtxt.readthedocs.io) - The original decentralised, minimalist microblogging service for hackers

- [yarn.social](https://yarn.social) - The multi-user pods allowed everyone to use twtxt as a social media without selfhosting

- [groovy-twtxt](https://git.mills.io/mckinley/groovy-twtxt) - A curated list of groovy twtxt-related projects

## Code by others

- [Slimdown](https://github.com/jbroadway/slimdown) - A simple regex-based Markdown parser in PHP.

- Tag cloud feature is based on php code by [Domingos Faria](https://social.dfaria.eu/search)

