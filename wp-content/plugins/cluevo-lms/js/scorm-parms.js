var scormErrors = {
  ElementIsReadOnly: 404,
  ElementIsWriteOnly: 405,
  ElementNotInitialized: 403,
  GetValueBeforeInit: 112,
  SetValueBeforeInit: 132
}

var scormParameterTypes = {
  'cmi._version': {type: 'string', mode: 'ro', default: '2004'}, // (characterstring, RO)
  'cmi.completion_status': {
    type: 'string',
    mode: 'rw',
    values: ['completed', 'incomplete', 'not attempted', 'unknown'],
    default: 'unknown'
  }, // (“completed”, “incomplete”, “not attempted”, “unknown”, RW)
  'cmi.completion_threshold': {type: 'real', mode: 'rw'}, // (real(10,7)
  'cmi.credit': {type: 'string', mode: 'ro', values: ['credit', 'no-credit'], default: 'credit' }, // (“credit”, “no-credit”, RO)
  'cmi.entry': {type: 'string', mode: 'ro', values: ['ab_initio', 'resume'], default: 'resume' }, // (ab_initio, resume, “”, RO)
  'cmi.exit': {
    type: 'string',
    mode: 'wo',
    values: ['timeout', 'suspend', 'logout', 'normal', ''],
  }, // (timeout, suspend, logout, normal, “”, WO)
  'cmi.launch_data': {type: 'string', mode: 'rw'}, // (characterstring (SPM: 4000)
  'cmi.learner_id': {type: 'string', mode: 'rw'}, // (long_identifier_type (SPM: 4000)
  'cmi.learner_name': {type: 'string', mode: 'rw'}, // (localized_string_type (SPM: 250)
  'cmi.location': {type: 'string', mode: 'rw'}, // (characterstring (SPM: 1000)
  'cmi.max_time_allowed': {type: 'real', digits: [10, 2]}, // (timeinterval (second,10,2)
  'cmi.mode': {
    type: 'string',
    mode: 'ro',
    values: ['browse', 'normal', 'review'],
    default: "normal"
  }, // (“browse”, “normal”, “review”, RO)
  'cmi.progress_measure': {type: 'real', mode: 'rw', digits: [10, 7]}, // (real (10,7)
  'cmi.scaled_passing_score': {type: 'real', mode: 'rw', digits: [10, 7]}, // (real(10,7)
  //'cmi.score._children': '', // (scaled,raw,min,max, RO)
  'cmi.score.scaled': {type: 'real', mode: 'rw', digits: [10, 7]}, // (real (10,7)
  'cmi.score.raw': {type: 'real', mode: 'rw', digits: [10, 7]}, // (real (10,7)
  'cmi.score.min': {type: 'real', mode: 'rw', digits: [10, 7]}, // (real (10,7)
  'cmi.score.max': {type: 'real', mode: 'rw', digits: [10, 7]}, // (real (10,7)
  'cmi.session_time': {type: 'real', mode: 'rw', digits: [10, 2]}, // (timeinterval (second,10,2)
  'cmi.success_status': {
    type: 'string',
    mode: 'rw',
    values: ['passed', 'failed', 'unknown'],
    default: 'unknown'
  }, // (“passed”, “failed”, “unknown”, RW)
  'cmi.suspend_data': {type: 'string', mode: 'rw'}, // (characterstring (SPM: 64000)
  'cmi.time_limit_action': {
    type: 'string',
    mode: 'ro',
    values: [
      'exit,message',
      'continue,message',
      'exit,no message',
      'continue,no message',
    ],
    default: 'exit,no message'
  }, // (“exit,message”, “continue,message”, “exit,no message”, “continue,no message”, RO)
  'cmi.total_time': {type: 'real', mode: 'rw', digits: [10, 2]}, // (timeinterval (second,10,2)
};

//'cmi.comments_from_learner._children': 'comment_', // (comment,location,timestamp, RO)
//'cmi.comments_from_learner._count': '', // (non-negative integer, RO)
//'cmi.comments_from_learner.n.comment': '', // (localized_string_type (SPM: 4000)
//'cmi.comments_from_learner.n.location': '', // (characterstring (SPM: 250)
//'cmi.comments_from_learner.n.timestamp': '', // (time (second,10,0)
//'cmi.comments_from_lms._children': '', // (comment,location,timestamp, RO)
//'cmi.comments_from_lms._count': '', // (non-negative integer, RO)
//'cmi.comments_from_lms.n.comment': '', // (localized_string_type (SPM: 4000)
//'cmi.comments_from_lms.n.location': '', // (characterstring (SPM: 250)
//'cmi.comments_from_lms.n.timestamp': '', // (time(second,10,0)
//'cmi.interactions._children': '', // (id,type,objectives,timestamp,correct_responses,weighting,learner_response,result,latency,description, RO)
//'cmi.interactions._count': '', // (non-negative integer, RO)
//'cmi.interactions.n.id': '', // (long_identifier_type (SPM: 4000)
//'cmi.interactions.n.type': '', // (“true-false”, “choice”, “fill-in”, “long-fill-in”, “matching”, “performance”, “sequencing”, “likert”, “numeric” or “other”, RW)
//'cmi.interactions.n.objectives._count': '', // (non-negative integer, RO)
//'cmi.interactions.n.objectives.n.id': '', // (long_identifier_type (SPM: 4000)
//'cmi.interactions.n.timestamp': '', // (time(second,10,0)
//'cmi.interactions.n.correct_responses._count': '', // (non-negative integer, RO)
//'cmi.interactions.n.correct_responses.n.pattern': '', // (format depends on interaction type, RW)
//'cmi.interactions.n.weighting': '', // (real (10,7)
//'cmi.interactions.n.learner_response': '', // (format depends on interaction type, RW)
//'cmi.interactions.n.result': '', // (“correct”, “incorrect”, “unanticipated”, “neutral”)
//'cmi.interactions.n.latency': '', // (timeinterval (second,10,2)
//'cmi.interactions.n.description': '', // (localized_string_type (SPM: 250)
//'cmi.learner_preference._children': '', // (audio_level,language,delivery_speed,audio_captioning, RO)
//'cmi.learner_preference.audio_level': '', // (real(10,7)
//'cmi.learner_preference.language': '', // (language_type (SPM 250)
//'cmi.learner_preference.delivery_speed': '', // (real(10,7)
//'cmi.learner_preference.audio_captioning': '', // (“-1”, “0”, “1”, RW)
//'cmi.objectives._children': '', // (id,score,success_status,completion_status,description, RO)
//'cmi.objectives._count': '', // (non-negative integer, RO)
//'cmi.objectives.n.id': '', // (long_identifier_type (SPM: 4000)
//'cmi.objectives.n.score._children': '', // (scaled,raw,min,max, RO)
//'cmi.objectives.n.score.scaled': '', // (real (10,7)
//'cmi.objectives.n.score.raw': '', // (real (10,7)
//'cmi.objectives.n.score.min': '', // (real (10,7)
//'cmi.objectives.n.score.max': '', // (real (10,7)
//'cmi.objectives.n.success_status': '', // (“passed”, “failed”, “unknown”, RW)
//'cmi.objectives.n.completion_status': '', // (“completed”, “incomplete”, “not attempted”, “unknown”, RW)
//'cmi.objectives.n.progress_measure': '', // (real (10,7)
//'cmi.objectives.n.description': '', // (localized_string_type (SPM: 250)
//'adl.nav.request': { type: 'string', mode: 'rw', values: [ 'continue', 'previous', 'choice', 'jump', 'exit', 'exitAll', 'abandon', 'abandonAll', 'suspendAll' } // (request(continue, previous, choice, jump, exit, exitAll, abandon, abandonAll, suspendAll _none_)
//'adl.nav.request_valid.continue': '', // (state (true, false, unknown)
//'adl.nav.request_valid.previous': '', // (state (true, false, unknown)
//'adl.nav.request_valid.choice.{target=}': '', // (state (true, false, unknown)
//'adl.nav.request_valid.jump.{target=}': '', // (state (true, false, unknown)
