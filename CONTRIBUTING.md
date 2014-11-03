CONTRIBUTING
============

Reporting a Bug
---------------

Please report bugs!  It helps us make *symfony-cron* better.

Before submitting a bug:

- Check the [tracker][1] to see if anyone has already reported the bug.

If your problem definitely looks like a new bug, report it using the
official bug [tracker][1] and follow some basic rules:

- Use the title field to clearly describe the issue;
- Describe the steps needed to reproduce the bug with short code
  examples (providing a unit test that illustrates the bug is best);
- Give as much detail as possible about your environment (OS, PHP
  version, Symfony version, enabled extensions, ...);
- _(optional)_ Attach a patch.

Submitting a Patch
------------------

### Step 1: Setup your Environment ###

#### Install the Software Stack ####

Before working on *symfony-cron*, setup a friendly environment with the
following software:

- Git;
- PHP version 5.3 or above

#### Configure Git ####

Set up your user information with your real name and a working email
address:

```bash
$ git config --global user.name "Your Name"
$ git config --global user.email you@example.com
```

If you are new to Git, you are highly recommended to read the excellent
and free [ProGit][2] book.

Windows users: when installing Git, the installer will ask what to do
with line endings, and suggests replacing all LF with CRLF.  This is the
wrong setting if you wish to contributed to *symfony-cron*!  Selecting
the as-is method is your best choice, as Git will convert your line
feeds to the ones in the repository.  If you have already installed Git,
you can check the value of this setting by typing:

```bash
$ git config core.autocrlf
```

This will return either "false", "input" or "true"; "true" and "false"
being the wrong values.  Change it to "input" by typing:

```bash
$ git config --global core.autocrlf input
```

Replace --global with --local if you want to set it only for the active
repository.

#### Get the Symfony-Cron Source Code ####

Get the *symfony-cron* source code:

- Create a [GitHub][3] account and sign in;
- Fork the [symfony-cron repository][4] (click on the "Fork" button);
- After the "forking action" has completed, clone your fork locally
  (this will create a `symfony-cron-bundle` directory):

```bash
$ git clone git@github.com:USERNAME/symfony-cron-bundle.git
```

- Add the upstream repository as a remote

```bash
$ cd symfony-cron
$ git remote add upstream git://github.com/course-hero/symfony-cron-bundle.git
```

#### Install All Dependencies Using Composer ####

Install all needed dependencies:

- Install [composer][5];
- Run

```bash
$ cd symfony-cron-bundle
$ php composer.phar install
```

Change `composer.phar` as needed to point to wherever composer was
installed.

### Step 2: Work on your Patch ###

#### The License ####

Before you start, you must know that all patches you are going to submit
must be released under the [Apache v2][6] license.

#### Choose the Right Branch ####

Before working on a patch, you must determine on which branch you need
to work.  The branch should be based on the `master` branch if you want
to add a new feature.  If you want to fix a bug, use the oldest but
still maintained version of *symfony-cron* where the bug likely happens.

All bug fixes merged into maintenance branches are also merged into more
recent branches on a regular basis.  For instance, if you submit a patch
for the `x` branch, the patch will also be applied by the core team on
the `master` branch.

#### Create a Topic Branch ####

Each time you want to work on a patch for a bug or on an enhancement,
create a topic branch:

```bash
$ git checkout -b BRANCH_NAME master
```

Or, if you want to provide a bugfix for the `x` branch, first track the
remote `x` branch locally:

```bash
$ git checkout -t origin/x
```

Then create a new branch off the `x` branch to work on the bugfix:

```bash
$ git checkout -b BRANCH_NAME x
```

Use a descriptive name for your branch (`ticket_xxx` where `xxx` is the
ticket number is a good convention for bug fixes).

The above checkout commands automatically switch the code to the newly
created branch (check the branch you are working on with `git branch`).

#### Work on your Patch ####

Work on the code as much as you want and commit as much as you want; but
keep in mind the following:

- Read about the Symfony [conventions][7] and follow the coding
  [standards][8] (use `git diff --check`` to check for trailing spaces
  -- also read the tip below);
- Add unit tests to prove that the bug is fixed or that the new feature
  actually works;
- Try hard to not break backward compatibility (if you must do so, try
  to provide a compatibility layer to support the old way) -- patches
  that break backward compatibility have less chance to be merged;
- Do atomic and logically separate commits (use the power of `git
  rebase` to have a clean and logical history;
- Squash irrelevant commits that are just about fixing code standards or
  fixing typos in your own code;
- Never fix coding standards in some existing code as it makes the code
  review more difficult;
- Write good commit messages (see the tip below).

A good commit message is composed of a summary (the first line),
optionally followed by a blank line and a more detailed description.
Use a verb (`fixed`, `added`, ...) to start the summary and don't add a
period at the end.  The summary line should be less than 50 characters ideally,
72 characters at maximum.  Each line should be wrapped at 72 characters.  Here
is an example:

```
Added CONTRIBUTING.md

The CONTRIBUTING.md file contains all of the information that a user or
developer might need to contribute to the project.  While it is
primarily written for developers who need technical knowledge about how
to contribute code to the project, it also covers the procedures needed
by regular users who simply integration symfony-cron with their
projects.
```

#### Prepare your Patch for Submission ####

When your patch is not about a bug fix (when you add a new feature or
change and existing one, for instance), it must also include the
following:

- An explanation of the changes in the [CHANGELOG][9] file (the `[BC
  BREAK]` or the `[DEPRECATION]` prefix must be used when relevant);
- An explanation on how to upgrade an existing application in the
  relevant [UPGRADE][10] file if the changes break backward
  compatibility.

### Step 3: Submit your Patch ###

Whenever you feel that your patch is ready for submission, follow the
following steps:

#### Rebase your Patch ####

Before submitting your patch, update your branch (needed if it takes you
a while to finish your changes):

```bash
$ git checkout master
$ git fetch upstream
$ git merge upstream/master
$ git checkout BRANCH_NAME
$ git rebase master
```

Replace `master` with the branch you selected previously (e.g. `x`) if
you are working on a bugfix.

When doing the `rebase` command, you might have to fix merge conflicts.
`git status` will show you the _unmerged_ files.  Resolve all the
conflicts, then continue the rebase.

```bash
$ git add ... # add resolved files
$ git rebase --continue
```

Check that all tests still pass and push your branch remotely.

```bash
$ git push --force origin BRANCH_NAME
```

#### Make a Pull Request ####

You can now make a pull request on the [symfony-cron][4] GitHub
repository.

Take care to point your pull request towards `symfony-cron-bundle:X` if
you want the core team to pull a bugfix based on the `X` branch.

The pull request description must include the following checklist at the
top to ensure that contributions may be reviewed without needless
feedback loops and that your contributions can be included into
*symfony-cron* as quickly as possible:

```
| Q             | A
| ------------- | ---
| Bug fix?      | [yes|no]
| New feature?  | [yes|no]
| BC breaks?    | [yes|no]
| Deprecations? | [yes|no]
| Tests pass?   | [yes|no]
| Fixed tickets | [comma separated list of tickets fixed by the PR]
| License       | Apache v2
| Doc PR        | [The reference to the documentation PR if any]
```

An example submission could now look as follows:

```
| Q             | A
| ------------- | ---
| Bug fix?      | yes
| New feature?  | no
| BC breaks?    | no
| Deprecations? | no
| Tests pass?   | yes
| Fixed tickets | #12
| License       | Apache v2
| Doc PR        | 
```

The whole table must be included (do *not* remove lines that you think
are not relevant).  For simple typos, minor changes in the PHPDocs, or
changes in translation files, use the shorter version of the checklist:

```
| Q             | A
| ------------- | ---
| Fixed tickets | #12
| License       | Apache v2
```

Some answers to the questions trigger some more requirements:

- If you answer yes to "Bug fix?", check if the bug is already listed in
  the [issue tracker][1] and reference it/them in "Fixed tickets";
- If you answer yes to "New feature?", you must submit a pull request to
  the documentation and reference it under the "Doc PR" section;
- If you answer yes to "BC breaks?", the patch must contain updates to
  the relevant [CHANGELOG][9] and [UPGRADE][10] files;
- If you answer yes to "Deprecations?", the patch must contain updates
  to the relevant [CHANGELOG][9] and [UPGRADE][10] files;
- If you answer no to "Tests pass", you must add an item to a todo-list
  with the actions that must be done to fix the tests;
- If the "license" is not Apache v2, just don't submit the pull request
  as it won't be accepted anyway.

If some of the previous requirements are not met, create a todo-list and
add relevant items:

```
- [ ] fix the tests as they have not been updated yet
- [ ] submit changes to the documentation
- [ ] document the BC breaks
```

If the code is not finished yet because you don't have time to finish it
or because you want early feedback on your work, add an item to the
todo-list:

```
- [ ] finish the code
- [ ] gather feedback for my changes
```

As long as you have items in the todo-list, please prefix the pull
request title with `[WIP]`.

In the pull request description, give as much detail as possible about
your changes (don't hesitate to give code examples to illustrate your
points).  If your pull request is about adding a new feature or
modifying an existing one, explain the rationale for the changes.  The
pull request description helps the code review and it serves as a
reference when the code is merged (the pull request description and all
its associated comments are part of the merge commit message).

#### Rework your Patch ####

Based on the feedback on the pull request, you might need to rework your
patch.  Before resubmitting the patch, rebase with `upstream/master` or
`upstream/X`, don't merge; and force the push to the origin:

```bash
$ git rebase -f upstream/master
$ git push --force origin BRANCH_NAME
```

When doing a `push --force`, always specify the branch name explicitly
to avoid messing other branches in the repo (`--force` tells Git that
you really want to mess with things so do it carefully).

Often, moderators will ask you to "squash" your commits.  This means you
will convert many commits into one commit.  To do this, use the rebase
command:

```bash
$ git rebase -i upstream/master
$ git push --force origin BRANCH_NAME
```

After you type this commit, an editor will popup showing a list of
commits:

```
pick 1a31be6 first commit
pick 7fc64b4 second commit
pick 7d33018 third commit
```

To squash all commits into the first one, remove the word `pick` before
the second and third commits, and replace it by the word `squash` or
just `s`.  When you save, Git will start rebasing, and if successful,
will ask you to edit the commit message, which by default is a listing
of the commit messages of all the commits.  When you are finished,
execute the push command.

[1]: https://github.com/course-hero/symfony-cron-bundle/issues
[2]: http://git-scm.com/book
[3]: https://github.com
[4]: https://github.com/course-hero/symfony-cron-bundle
[5]: https://getcomposer.org/download/
[6]: http://www.apache.org/licenses/LICENSE-2.0.txt
[7]: http://symfony.com/doc/current/contributing/code/conventions.html
[8]: http://symfony.com/doc/current/contributing/code/standards.html
[9]: CHANGELOG.md
[10]: UPGRADE.md
