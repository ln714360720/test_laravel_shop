<?php
return[
    'alipay'=>[
        'app_id'=>'2016092300580130',
        'app_public_key'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtFbkpDbmTm3eKNfb2S4SG+RZRcETH05MvzZstewQRzp0vZwubHb4y0sHOaj7DDkipMfpZdY4Ea41VjZ478Ik5ypopPIvkdUBex3+V75zgX/QrcmPAYV9OCgFTbQHvmARqU9p+KYtcbVQ5w8hwvlpbCKRT0LmxqKf21zYMnfJQ1rZyn9bO1y5WNxJiw0LLiqwqUYZ5ttbwp/BeVAmzMXiGsgLWcPItjS5dmTjtRQZbDSIme2cZTfRd62c4c8y/evgZ3hhACxUb8hrN+IJ846acCLYxG+aOoZVunSX7qwv+BvuS4ybVLVYvHArs0zE9KuOVKAURwEMKo0BxuI7YJ/pCwIDAQAB',
        
        'private_key'=>'MIIEowIBAAKCAQEAuHFJ45447t2cDB5bmvnZprXP9nWqycCENUR6XFeChJnm2hxsf5TXEW4dbxbSU4OHSDf9zbXHi1S+HU8rKALcanIEWlG1et/jI4uBCEsE18njPa86L5AACEMr4IFkZBuZDzDYvoDOtbNzzDXmypL6QWH1bNRwaQyC19jVriy1OYRnnTKglB14C4rfnl9Cwi23QEb9mblMD12ra0i9Anm23SmHQPNLyEw76OL6u56tqIyuoRq48JCI8fUNYJeCyDOxJ9WEu1QkHWdi9gvumS5rq3pEJGMRmYif1jlwmj8OQrDWx5Ej/IBkC77HnSJnys95C5EMoIbso4E/X/ovGEooAwIDAQABAoIBAER+BXNaYUgK0ZUKfPgbCkFHSPf8EDUlobLqQAokkRpO0JR0c7IZApi9bH7BWc+bi1Q9PqnydCyAhqz5pkwQa+u3dXhY2WzM0Vt0xbDfsuezWAijFpdtASYQU63mPvKR8Q/cEtEDoj+FaV4PMipN1FMNXodQiIHqHHitVAR/I7k8mJBE3P9G8Ee1JSumOuir7iL5/OefZLmlrGwg3P/f5xcZOAvpeEMVw+jcbMEV16xp+bZ/g7M2bV/egfLJOBgm4gzY3cvfiJ1QBZU7SXJMwCvHtkuQo1xNbiDy85FtPLP7ZH1olAFQ9EI/aQeEzPwsTnNYe1cajllEH2aMY8Hy9akCgYEA854E5zrj8u2sr/RWUxhk/dOZROokCfr6GS9CRBGuCUv8pQNAkKZXaxomdeGzpYz7iLl0KZGce707BwZDph7owabR0T2Iey+QM0xblF87t2m7nNUIaX0ToGczenmPGaO9fO3wPvpK6joYWUyhjQc33FA96WYKJUfyTpODcwLgyM0CgYEAwdFHpoSjGn1axilB7r0T2k7K/IbZuO7Xsykxfl8Dq7nsK2jXbgiIHN0UrAIKqiuvi+mtqzT7OCbad5uAVl9QJp2bGrTJA7WWx5iY0HwhnR66dKiDeJZM5Rx66pWbjtObhe/q3r2NUv3H2MRhzBnS2TV7cGZiVv73Kn+oE3Cn9A8CgYEAxQpHLvr7yc2gwcQfWiA99usBabLzKTtcs6f8se+W0yApnRGvVA+mXxMMjoXZ9om8HI7bPI/wgEjCiGDxsFgJrC+QPuvFCtWijUsyOyR3uVaEj0ni/udSS4eNJH9TVcqRBY2xpk5s71vDu952QAnZjZE9Mhz9EcBKZHF9fTWTt/UCgYBp8q35L849H8MsScdZ6w/cKXA6xLhlqGJO9LiyfNvz1qlsPV5uLsnBBXVUZbVQupq2n+Gokki1tD9+XIm2LVoSEduEqMitd2lZ6Ge4p/J0AiUouilMFNUp9PyYGXo0hCYi/Dhm1DVZ5ZKGQyu2t3MT+3FjywP8zFluaOQG5HFbLwKBgFrzW/X/IiTpbhfR3cjQuDxfWH57cEBQ1MDivsQIcyqak1xtB+eEVoz6af9KixE29C6Gd1DqgV32fsRnY4/clzYFG2XBMuUTP0dpKXsYvVM3g0t8NFu8wpjkrLwG3vOFUQWfHKFGT9+DMILN+XSg6nDjXnIsdRpaLmpO3Fzx+hbz',
        'log'=>[
            'file'=>storage_path('logs/alipay.log'),
        ],
    ],
    'wechat'=>[
        'app_id'=>'wxf67448083fee7de6',//公共号id
        'mch_id'=>'1485722102',
        'key'=>'6b4af58d7631adf709ebeb0b1c673ccf ',
        'cert_client'=>resource_path('wechat_pay/apiclient_cert.pem'),
        'cert_key'=>resource_path('wechat_pay/apiclient_key.pem'),
        'log'=>[
            'file'=>storage_path('logs/wechat_pay.log'),
        ],
    ],
];