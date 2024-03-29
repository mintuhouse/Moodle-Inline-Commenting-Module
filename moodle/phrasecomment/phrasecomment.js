// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Comment Helper
 * @author Dongsheng Cai <dongsheng@moodle.com>
 */
M.core_phrasecomment = {
    /**
     * Initialize commenting system
     */
    init: function(Y, options) {
        var CommentHelper = function(args) {
            CommentHelper.superclass.constructor.apply(this, arguments);
        };
        CommentHelper.NAME = "COMMENT";
        CommentHelper.ATTRS = {
            options: {},
            lang: {}
        };
        Y.extend(CommentHelper, Y.Base, {
            api: M.cfg.wwwroot+'/phrasecomment/comment_ajax.php',
            initializer: function(args) {
                var scope = this;
                this.client_id = args.client_id;
                this.itemid = args.itemid;
                this.commentarea = args.commentarea;
                this.component = args.component;
                this.courseid = args.courseid;
                this.contextid = args.contextid;
                this.attemptid = args.attemptid;
                this.phraseid = args.phraseid;
                this.startnum = args.startnum;
                this.endnum = args.endnum;
                this.qid = args.qid;
                this.instanceid = args.instanceid;
                this.autostart = (args.autostart);
                // expand comments?
                if (this.autostart) {
                    this.view(args.page);
                }
                // load comments
                var handle = Y.one('#comment-link-'+this.client_id);
                // hide toggle link
                if (handle) {
                    if (args.notoggle) {
                        handle.setStyle('display', 'none');
                    }
                    handle.on('click', function(e) {
                        e.preventDefault();
                        this.view(0);
                        return false;
                    }, this);
                }
                scope.toggle_textarea(false);
                CommentHelper.confirmoverlay = new Y.Overlay({
bodyContent: '<div class="comment-delete-confirm"><a href="#" id="confirmdelete-'+this.client_id+'">'+M.str.moodle.yes+'</a> <a href="#" id="canceldelete-'+this.client_id+'">'+M.str.moodle.no+'</a></div>',
                                        visible: false
                                        });
                CommentHelper.confirmoverlay.render(document.body);
            },
            post: function() {
                var ta = Y.one('#dlg-content-'+this.client_id);
                var scope = this;
                var value = ta.get('value');
                if (value && value != M.str.moodle.addcomment) {
                    var params = {'content': value};
                    this.request({
                        action: 'add',
                        scope: scope,
                        params: params,
                        callback: function(id, obj, args) {
                            var scope = args.scope;
                            var cid = scope.client_id;
                            var ta = Y.one('#dlg-content-'+cid);
                            ta.set('value', '');
                            scope.toggle_textarea(false);
                            var container = Y.one('#comment-list-'+cid);
                            var result = scope.render([obj], true);
                            var newcomment = Y.Node.create(result.html);
                            container.appendChild(newcomment);
                            var ids = result.ids;
                            var linktext = Y.one('#comment-link-text-'+cid);
                            if (linktext) {
                                linktext.set('innerHTML', M.str.moodle.comments + ' ('+obj.count+')');
                            }
                            for(var i in ids) {
                                var attributes = {
                                    color: { to: '#06e' },
                                    backgroundColor: { to: '#FFE390' }
                                };
                                var anim = new YAHOO.util.ColorAnim(ids[i], attributes);
                                anim.animate();
                            }
                            scope.register_pagination();
                            scope.register_delete_buttons();
                        }
                    }, true);
                } else {
                    var attributes = {
                        backgroundColor: { from: '#FFE390', to:'#FFFFFF' }
                    };
                    var anim = new YAHOO.util.ColorAnim('dlg-content-'+cid, attributes);
                    anim.animate();
                }
            },
            resolve: function(){
				var scope = this;
                this.request({
					action: 'resolve',
                    scope: scope,
                    callback: function(id,obj,args) {
							var scope=args.scope;
							var cid = scope.client_id;
                            var ta = Y.one('#dlg-content-'+cid);
                            ta.setStyle('display','none');
                            ta=Y.one('#content-action-'+cid);
                            ta.setStyle('display','none');
                            ta=Y.one('#content-action-r-'+cid);
                            ta.setStyle('display','none');
                    }
                }, true);
                var cid = scope.client_id;
                            var ta = Y.one('#dlg-content-'+cid);
                            ta.setStyle('display','none');
                            ta=Y.one('#comment-action-'+cid);
                            ta.setStyle('display','none');
                            ta=Y.one('#comment-action-r-'+cid);
                            ta.setStyle('display','none');
                
                
			},
            request: function(args, noloading) {
                var params = {};
                var scope = this;
                if (args['scope']) {
                    scope = args['scope'];
                }
                //params['page'] = args.page?args.page:'';
                // the form element only accept certain file types
                params['sesskey']   = M.cfg.sesskey;
                params['action']    = args.action?args.action:'';
                params['client_id'] = this.client_id;
                params['itemid']    = this.itemid;
                params['area']      = this.commentarea;
                params['courseid']  = this.courseid;
                params['contextid'] = this.contextid;
                params['component'] = this.component;
                params['attemptid'] = this.attemptid;
                params['phraseid']  = this.phraseid;
                params['startnum']  = this.startnum;
                params['endnum']    = this.endnum;
                params['qid']       = this.qid;
                params['instanceid'] = this.instanceid;
                if (args['params']) {
                    for (i in args['params']) {
                        params[i] = args['params'][i];
                    }
                }
                var cfg = {
                    method: 'POST',
                    on: {
                        complete: function(id,o,p) {
							
                            if (!o) {
                                alert('IO FATAL');
                                return false;
                            }
                            var data = Y.JSON.parse(o.responseText);
                            if (data.error) {
                                if (data.error == 'require_login') {
                                    args.callback(id,data,p);
                                    return true;
                                }
                                alert(data.error);
                                return false;
                            } else {
								
                                args.callback(id,data,p);
                                return true;
                            }
                        }
                    },
                    arguments: {
                        scope: scope
                    },
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'User-Agent': 'MoodleComment/3.0'
                    },
                    data: build_querystring(params)
                };
                if (args.form) {
                    cfg.form = args.form;
                }
                
                Y.io(this.api, cfg);
                if (!noloading) {
                    this.wait();
                }
            },
            render: function(list, newcmt) {
                var ret = {};
                ret.ids = [];
                var template = Y.one('#cmt-tmpl');
                var html = '';
                for(var i in list) {
                    var htmlid = 'comment-'+list[i].id+'-'+this.client_id;
                    var val = template.get('innerHTML');
                    if (list[i].profileurl) {
                        val = val.replace('___name___', '<a href="'+list[i].profileurl+'">'+list[i].fullname+'</a>');
                    } else {
                        val = val.replace('___name___', list[i].fullname);
                    }
                    if (list[i]['delete']||newcmt) {
                        list[i].content = '<div class="comment-delete"><a href="#" id ="comment-delete-'+this.client_id+'-'+list[i].id+'" title="'+M.str.moodle.deletecomment+'"><img src="'+M.util.image_url('t/delete', 'core')+'" /></a></div>' + list[i].content;
                    }
                    val = val.replace('___time___', list[i].time);
                    val = val.replace('___picture___', list[i].avatar);
                    val = val.replace('___content___', list[i].content);
                    val = '<li id="'+htmlid+'">'+val+'</li>';
                    ret.ids.push(htmlid);
                    html = (val+html);
                }
                ret.html = html;
                return ret;
            },
            load: function(page) {
                var scope = this;
                var container = Y.one('#comment-ctrl-'+this.client_id);
                var params = {
                    'action': 'get',
                    'page': page
                };
                this.request({
                    scope: scope,
                    params: params,
                    callback: function(id, ret, args) {
                        var linktext = Y.one('#comment-link-text-'+scope.client_id);
                        if (ret.count && linktext) {
                            linktext.set('innerHTML', M.str.moodle.comments + ' ('+ret.count+')');
                        }
                        var container = Y.one('#comment-list-'+scope.client_id);
                        var pagination = Y.one('#comment-pagination-'+scope.client_id);
                        if (ret.pagination) {
                            pagination.set('innerHTML', ret.pagination);
                        } else {
                            //empty paging bar
                            pagination.set('innerHTML', '');
                        }
                        if (ret.error == 'require_login') {
                            var result = {};
                            result.html = M.str.moodle.commentsrequirelogin;
                        } else {
                            var result = scope.render(ret.list);
                        }
                        container.set('innerHTML', result.html);
                        var img = Y.one('#comment-img-'+scope.client_id);
                        if (img) {
                            img.set('src', M.util.image_url('t/expanded', 'core'));
                        }
                        args.scope.register_pagination();
                        args.scope.register_delete_buttons();
                    }
                });
            },

            dodelete: function(id) { // note: delete is a reserved word in javascript, chrome and safary do not like it at all here!
                var scope = this;
                var params = {'commentid': id};
                scope.cancel_delete();
                function remove_dom(type, anim, cmt) {
                    cmt.remove();
                }
                this.request({
                    action: 'delete',
                    scope: scope,
                    params: params,
                    callback: function(id, resp, args) {
                        var htmlid= 'comment-'+resp.commentid+'-'+resp.client_id;
                        var attributes = {
                            width:{to:0},
                            height:{to:0}
                        };
                        var cmt = Y.one('#'+htmlid);
                        cmt.setStyle('overflow', 'hidden');
                        var anim = new YAHOO.util.Anim(htmlid, attributes, 1, YAHOO.util.Easing.easeOut);
                        anim.onComplete.subscribe(remove_dom, cmt, this);
                        anim.animate();
                    }
                }, true);
            },
            register_actions: function() {
                // add new comment
                var action_btn = Y.one('#comment-action-post-'+this.client_id);
                if (action_btn) {
                    action_btn.on('click', function(e) {
                        e.preventDefault();
                        this.post();
                        return false;
                    }, this);
                }
                // cancel comment box
                var cancel = Y.one('#comment-action-cancel-'+this.client_id);
                if (cancel) {
                    cancel.on('click', function(e) {
                        e.preventDefault();
                        this.view(0);
                        return false;
                    }, this);
                }
                var resolve_but = Y.one('#comment-action-resolve-'+this.client_id);
                if(resolve_but){
					resolve_but.on('click',function(e){
							e.preventDefault();
							this.resolve();
							return false;						
					},this);
				}
            },
            register_delete_buttons: function() {
                var scope = this;
                // page buttons
                Y.all('div.comment-delete a').each(
                    function(node, id) {
                        var theid = node.get('id');
                        var parseid = new RegExp("comment-delete-"+scope.client_id+"-(\\d+)", "i");
                        var commentid = theid.match(parseid);
                        if (!commentid) {
                            return;
                        }
                        if (commentid[1]) {
                            Y.Event.purgeElement('#'+theid, false, 'click');
                        }
                        node.on('click', function(e, node) {
                            e.preventDefault();
                            var width = CommentHelper.confirmoverlay.bodyNode.getStyle('width');
                            var re = new RegExp("(\\d+).*", "i");
                            var result = width.match(re);
                            if (result[1]) {
                                width = Number(result[1]);
                            } else {
                                width = 0;
                            }
                            //CommentHelper.confirmoverlay.set('xy', [e.pageX-(width/2), e.pageY]);
                            CommentHelper.confirmoverlay.set('xy', [e.pageX-width-5, e.pageY]);
                            CommentHelper.confirmoverlay.set('visible', true);
                            Y.one('#canceldelete-'+scope.client_id).on('click', function(e) {
								e.preventDefault();
                                scope.cancel_delete();
                                });
                            Y.Event.purgeElement('#confirmdelete-'+scope.client_id, false, 'click');
                            Y.one('#confirmdelete-'+scope.client_id).on('click', function(e) {
									e.preventDefault();
                                    if (commentid[1]) {
										
                                        scope.dodelete(commentid[1]);
                                    }
                                });
                        }, scope, node);
                    }
                );
            },
            cancel_delete: function() {
                CommentHelper.confirmoverlay.set('visible', false);
            },
            register_pagination: function() {
                var scope = this;
                // page buttons
                Y.all('#comment-pagination-'+this.client_id+' a').each(
                    function(node, id) {
                        node.on('click', function(e, node) {
                            e.preventDefault();
                            var id = node.get('id');
                            var re = new RegExp("comment-page-"+this.client_id+"-(\\d+)", "i");
                            var result = id.match(re);
                            this.load(result[1]);
                        }, scope, node);
                    }
                );
            },
            view: function(page) {
                var container = Y.one('#comment-ctrl-'+this.client_id);
                var ta = Y.one('#dlg-content-'+this.client_id);
                var img = Y.one('#comment-img-'+this.client_id);
                var d = container.getStyle('display');
                if (d=='none'||d=='') {
                    // show
                    if (!this.autostart) {
                        this.load(page);
                    } else {
                        this.register_delete_buttons();
                        this.register_pagination();
                    }
                    container.setStyle('display', 'block');
                    if (img) {
                        img.set('src', M.util.image_url('t/expanded', 'core'));
                    }
                } else {
                    // hide
                    container.setStyle('display', 'none');
                    img.set('src', M.util.image_url('t/collapsed', 'core'));
                    if (ta) {
                        ta.set('value','');
                    }
                }
                if (ta) {
                    //toggle_textarea.apply(ta, [false]);
                    //// reset textarea size
                    ta.on('click', function() {
                        this.toggle_textarea(true);
                    }, this);
                    //ta.onkeypress = function() {
                        //if (this.scrollHeight > this.clientHeight && !window.opera)
                            //this.rows += 1;
                    //}
                    ta.on('blur', function() {
                        this.toggle_textarea(false);
                    }, this);
                }
                this.register_actions();
                return false;
            },
            toggle_textarea: function(focus) {
                var t = Y.one('#dlg-content-'+this.client_id);
				if(t){
		            if (focus) {
		                if (t.get('value') == M.str.moodle.addcomment) {
		                    t.set('value', '');
		                    t.setStyle('color', 'black');
		                }
		            }else{
		                if (t.get('value') == '') {
		                    t.set('value', M.str.moodle.addcomment);
		                    t.setStyle('color','grey');
		                    t.set('rows', 2);
		                }
		            }
				}		
            },
            wait: function() {
                var container = Y.one('#comment-list-'+this.client_id);
                container.set('innerHTML', '<div class="mdl-align"><img src="'+M.util.image_url('i/loading_small', 'core')+'" /></div>');
            },
        });

        new CommentHelper(options);
    },
    init_admin: function(Y) {
        var select_all = Y.one('#comment_select_all');
        select_all.on('click', function(e) {
            var comments = document.getElementsByName('comments');
            var checked = false;
            for (var i in comments) {
                if (comments[i].checked) {
                    checked=true;
                }
            }
            for (i in comments) {
                comments[i].checked = !checked;
            }
            this.set('checked', !checked);
        });

        var comments_delete = Y.one('#comments_delete');
        if (comments_delete) {
            comments_delete.on('click', function(e) {
                e.preventDefault();
                var list = '';
                var comments = document.getElementsByName('comments');
                for (var i in comments) {
                    if (typeof comments[i] == 'object' && comments[i].checked) {
                        list += (comments[i].value + '-');
                    }
                }
                if (!list) {
                    return;
                }
                var args = {};
                args.message = M.str.admin.confirmdeletecomments;
                args.callback = function() {
                    var url = M.cfg.wwwroot + '/phrasecomment/index.php';

                    var data = {
                        'commentids': list,
                        'sesskey': M.cfg.sesskey,
                        'action': 'delete'
                    };
                    var cfg = {
                        method: 'POST',
                        on: {
                            complete: function(id,o,p) {
                                if (!o) {
                                    alert('IO FATAL');
                                    return;
                                }
                                if (o.responseText == 'yes') {
                                    location.reload();
                                }
                            }
                        },
                        arguments: {
                            scope: this
                        },
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'User-Agent': 'MoodleComment/3.0'
                        },
                        data: build_querystring(data)
                    };
                    Y.io(url, cfg);
                };
                M.util.show_confirm_dialog(e, args);
            });
        }
    }
};
